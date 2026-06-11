<?php

namespace App\Service;

use App\Entity\LimitConfig;
use App\Entity\SelfExclusion;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ResponsibleGamingService
{
    private const ALLOWED_FIELDS = ['depositDaily', 'depositWeekly', 'betDaily', 'betWeekly'];

    public function __construct(private readonly EntityManagerInterface $em) {}

    // US-42 / US-43 — réduction immédiate, augmentation après 48h
    public function setLimit(User $user, string $field, ?float $value): void
    {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException('Champ de limite invalide.');
        }

        $config = $this->getOrCreateConfig($user);
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);

        $current = $config->$getter() !== null ? (float) $config->$getter() : null;

        // Réduction ou suppression → immédiat
        if ($value === null || $current === null || $value <= $current) {
            $config->$setter($value !== null ? (string) $value : null);

            // Annule un éventuel pending increase sur ce champ
            $pending = $config->getPendingIncrease() ?? [];
            unset($pending[$field]);
            $config->setPendingIncrease(empty($pending) ? null : $pending);

            $this->em->flush();
            return;
        }

        // Augmentation → différée 48h
        $pending          = $config->getPendingIncrease() ?? [];
        $pending[$field]  = [
            'value'     => $value,
            'appliesAt' => (new \DateTimeImmutable('+48 hours'))->format(\DateTimeInterface::ATOM),
        ];
        $config->setPendingIncrease($pending);
        $this->em->flush();
    }

    // Applique les pending increases dont le délai est écoulé
    public function applyPendingIncreases(User $user): void
    {
        $config  = $user->getLimitConfig();
        if ($config === null) {
            return;
        }

        $pending = $config->getPendingIncrease() ?? [];
        if (empty($pending)) {
            return;
        }

        $now     = new \DateTimeImmutable();
        $changed = false;

        foreach ($pending as $field => $data) {
            $appliesAt = new \DateTimeImmutable($data['appliesAt']);
            if ($now >= $appliesAt) {
                $setter = 'set' . ucfirst($field);
                $config->$setter((string) $data['value']);
                unset($pending[$field]);
                $changed = true;
            }
        }

        if ($changed) {
            $config->setPendingIncrease(empty($pending) ? null : $pending);
            $this->em->flush();
        }
    }

    // US-44 — auto-exclusion pour une période définie
    public function selfExclude(User $user, \DateTimeImmutable $endDate): void
    {
        $now = new \DateTimeImmutable();

        if ($endDate <= $now) {
            throw new \LogicException('La date de fin doit être dans le futur.');
        }

        $exclusion = (new SelfExclusion())
            ->setUser($user)
            ->setStartDate($now)
            ->setEndDate($endDate);

        $this->em->persist($exclusion);
        $this->em->flush();
    }

    private function getOrCreateConfig(User $user): LimitConfig
    {
        if ($user->getLimitConfig() !== null) {
            return $user->getLimitConfig();
        }

        $config = (new LimitConfig())->setUser($user);
        $this->em->persist($config);

        return $config;
    }
}
