<?php

namespace App\DataFixtures;

use App\Entity\Bet;
use App\Entity\LimitConfig;
use App\Entity\Outcome;
use App\Entity\SelfExclusion;
use App\Entity\SportEvent;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // ----------------------------------------------------------------
        // 1 — ADMIN
        // ----------------------------------------------------------------
        $admin = $this->makeUser($manager, 'admin@betsport.fr', 'Admin1234!', ['ROLE_ADMIN'], '1980-05-12', '0.00', true);

        // ----------------------------------------------------------------
        // 2 — MANAGERS
        // ----------------------------------------------------------------
        $manager1 = $this->makeUser($manager, 'manager1@betsport.fr', 'Manager1234!', ['ROLE_MANAGER'], '1988-09-14', '0.00', true);
        $manager2 = $this->makeUser($manager, 'manager2@betsport.fr', 'Manager1234!', ['ROLE_MANAGER'], '1992-03-27', '0.00', true);

        // ----------------------------------------------------------------
        // 3 — 10 UTILISATEURS (états variés)
        // ----------------------------------------------------------------
        $regularUsers = [];

        for ($i = 1; $i <= 10; $i++) {
            $birthdate = $faker->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d');
            $wallet    = (string) $faker->randomFloat(2, 50, 800);
            $active    = $i !== 7; // user7 suspendu

            $user = $this->makeUser(
                $manager,
                "user{$i}@betsport.fr",
                'User1234!',
                [],
                $birthdate,
                $wallet,
                $active
            );

            // user8 → auto-exclu 30 jours
            if ($i === 8) {
                $exclusion = (new SelfExclusion())
                    ->setUser($user)
                    ->setStartDate(new \DateTimeImmutable('-1 day'))
                    ->setEndDate(new \DateTimeImmutable('+30 days'));
                $manager->persist($exclusion);
            }

            // user9 → plafonds configurés
            if ($i === 9) {
                $limit = (new LimitConfig())
                    ->setUser($user)
                    ->setDepositDaily('100.00')
                    ->setDepositWeekly('500.00')
                    ->setBetDaily('50.00')
                    ->setBetWeekly('200.00');
                $manager->persist($limit);
            }

            $regularUsers[] = $user;
        }

        $manager->flush();

        // ----------------------------------------------------------------
        // 4 — 10 ÉVÉNEMENTS (statuts variés)
        // ----------------------------------------------------------------
        $sports = ['Football', 'Basketball', 'Tennis', 'Rugby', 'Handball'];

        $eventDefs = [
            ['PSG vs Real Madrid',       'Football',   '+5 days',  SportEvent::STATUS_PUBLIE],
            ['Lakers vs Bulls',          'Basketball', '+3 days',  SportEvent::STATUS_PUBLIE],
            ['Djokovic vs Alcaraz',      'Tennis',     '+7 days',  SportEvent::STATUS_PUBLIE],
            ['France vs Angleterre',     'Rugby',      '+10 days', SportEvent::STATUS_BROUILLON],
            ['Barcelone vs Juventus',    'Football',   '+12 days', SportEvent::STATUS_BROUILLON],
            ['Warriors vs Celtics',      'Basketball', '-2 days',  SportEvent::STATUS_FERME],
            ['Nadal vs Federer',         'Tennis',     '-5 days',  SportEvent::STATUS_TERMINE],
            ['XV de France vs All Blacks','Rugby',     '-8 days',  SportEvent::STATUS_TERMINE],
            ['Marseille vs Lyon',        'Football',   '-1 day',   SportEvent::STATUS_ANNULE],
            ['Paris vs Nantes',          'Handball',   '+15 days', SportEvent::STATUS_PUBLIE],
        ];

        $events = [];
        foreach ($eventDefs as $i => [$name, $sport, $dateOffset, $status]) {
            $mgr   = ($i % 2 === 0) ? $manager1 : $manager2;
            $event = (new SportEvent())
                ->setName($name)
                ->setSport($sport)
                ->setParticipants(explode(' vs ', $name))
                ->setEventDate(new \DateTime($dateOffset))
                ->setStatus($status)
                ->setManager($mgr);
            $manager->persist($event);
            $events[] = $event;
        }

        $manager->flush();

        // ----------------------------------------------------------------
        // 5 — OUTCOMES pour chaque événement
        // ----------------------------------------------------------------
        $outcomeSets = [
            ['Victoire PSG', 'Match nul', 'Victoire Real Madrid'],
            ['Victoire Lakers', 'Victoire Bulls'],
            ['Victoire Djokovic', 'Victoire Alcaraz'],
            ['Victoire France', 'Match nul', 'Victoire Angleterre'],
            ['Victoire Barcelone', 'Match nul', 'Victoire Juventus'],
            ['Victoire Warriors', 'Victoire Celtics'],
            ['Victoire Nadal', 'Victoire Federer'],
            ['Victoire France', 'Victoire All Blacks'],
            ['Victoire Marseille', 'Match nul', 'Victoire Lyon'],
            ['Victoire Paris', 'Match nul', 'Victoire Nantes'],
        ];

        $allOutcomes = [];
        foreach ($events as $i => $event) {
            $outcomes = [];
            foreach ($outcomeSets[$i] as $j => $label) {
                $odds    = round($faker->randomFloat(2, 1.5, 5.0), 2);
                $outcome = (new Outcome())
                    ->setLabel($label)
                    ->setOdds((string) $odds)
                    ->setSportEvent($event);

                // Pour les événements TERMINE, marquer un gagnant
                if ($event->getStatus() === SportEvent::STATUS_TERMINE && $j === 0) {
                    $outcome->setIsWinner(true);
                }

                $manager->persist($outcome);
                $outcomes[] = $outcome;
            }
            $allOutcomes[$i] = $outcomes;
        }

        $manager->flush();

        // ----------------------------------------------------------------
        // 6 — PARIS variés sur les événements publiés / fermés / terminés
        // ----------------------------------------------------------------
        $bettableIndexes = [0, 1, 2, 5, 6, 7]; // publiés, fermés, terminés

        foreach ($regularUsers as $u) {
            $nbBets = $faker->numberBetween(1, 4);

            for ($b = 0; $b < $nbBets; $b++) {
                $eventIndex = $faker->randomElement($bettableIndexes);
                $event      = $events[$eventIndex];
                $outcomes   = $allOutcomes[$eventIndex];
                $outcome    = $faker->randomElement($outcomes);
                $amount     = (float) $faker->randomFloat(2, 5, 80);

                // Ne pas parier si solde insuffisant
                if ((float) $u->getWallet() < $amount) {
                    continue;
                }

                $status = Bet::STATUS_EN_ATTENTE;
                if ($event->getStatus() === SportEvent::STATUS_TERMINE) {
                    $status = $outcome->isWinner() ? Bet::STATUS_GAGNE : Bet::STATUS_PERDU;
                } elseif ($event->getStatus() === SportEvent::STATUS_ANNULE) {
                    $status = Bet::STATUS_ANNULE;
                }

                $bet = (new Bet())
                    ->setUser($u)
                    ->setSportEvent($event)
                    ->setOutcome($outcome)
                    ->setAmount((string) $amount)
                    ->setOddsAtBet($outcome->getOdds())
                    ->setStatus($status)
                    ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-30 days')->format('Y-m-d H:i:s')));

                $manager->persist($bet);

                // Débit portefeuille
                $u->setWallet((string) round((float) $u->getWallet() - $amount, 2));

                // Transaction mise
                $tx = (new Transaction())
                    ->setUser($u)
                    ->setAmount((string) $amount)
                    ->setType(Transaction::TYPE_MISE)
                    ->setCreatedAt($bet->getCreatedAt());
                $manager->persist($tx);

                // Créditer si gagné
                if ($status === Bet::STATUS_GAGNE) {
                    $gain = round($amount * (float) $outcome->getOdds(), 2);
                    $u->setWallet((string) round((float) $u->getWallet() + $gain, 2));

                    $txGain = (new Transaction())
                        ->setUser($u)
                        ->setAmount((string) $gain)
                        ->setType(Transaction::TYPE_GAIN)
                        ->setCreatedAt(new \DateTimeImmutable());
                    $manager->persist($txGain);
                }
            }

            // Quelques dépôts initiaux par utilisateur
            for ($d = 0; $d < $faker->numberBetween(1, 3); $d++) {
                $depotAmount = (float) $faker->randomFloat(2, 50, 300);
                $txDepot     = (new Transaction())
                    ->setUser($u)
                    ->setAmount((string) $depotAmount)
                    ->setType(Transaction::TYPE_DEPOT)
                    ->setCreatedAt(new \DateTimeImmutable($faker->dateTimeBetween('-60 days')->format('Y-m-d H:i:s')));
                $manager->persist($txDepot);
            }
        }

        $manager->flush();
    }

    private function makeUser(
        ObjectManager $manager,
        string $email,
        string $password,
        array $roles,
        string $birthdate,
        string $wallet,
        bool $active
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $user->setBirthdate(new \DateTime($birthdate));
        $user->setWallet($wallet);
        $user->setIsActive($active);
        $manager->persist($user);
        return $user;
    }
}
