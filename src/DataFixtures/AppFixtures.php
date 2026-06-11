<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email'     => 'admin@betsport.fr',
                'password'  => 'Admin1234!',
                'roles'     => ['ROLE_ADMIN'],
                'birthdate' => '1985-03-15',
                'wallet'    => '0.00',
                'active'    => true,
            ],
            [
                'email'     => 'manager@betsport.fr',
                'password'  => 'Manager1234!',
                'roles'     => ['ROLE_MANAGER'],
                'birthdate' => '1990-07-22',
                'wallet'    => '0.00',
                'active'    => true,
            ],
            [
                'email'     => 'user@betsport.fr',
                'password'  => 'User1234!',
                'roles'     => [],
                'birthdate' => '1995-11-30',
                'wallet'    => '250.00',
                'active'    => true,
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setRoles($data['roles']);
            $user->setBirthdate(new \DateTime($data['birthdate']));
            $user->setWallet($data['wallet']);
            $user->setIsActive($data['active']);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
