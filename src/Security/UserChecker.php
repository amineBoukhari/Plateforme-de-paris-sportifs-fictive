<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\SelfExclusionRepository;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(private SelfExclusionRepository $selfExclusionRepository) {}

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été suspendu. Contactez l\'administrateur.');
        }

        $exclusion = $this->selfExclusionRepository->findActiveByUser($user->getId());
        if ($exclusion !== null) {
            throw new CustomUserMessageAccountStatusException(
                sprintf(
                    'Vous êtes auto-exclu jusqu\'au %s.',
                    $exclusion->getEndDate()->format('d/m/Y')
                )
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}
