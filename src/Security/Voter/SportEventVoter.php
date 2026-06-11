<?php

namespace App\Security\Voter;

use App\Entity\SportEvent;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SportEventVoter extends Voter
{
    public const EDIT    = 'sport_event_edit';
    public const DELETE  = 'sport_event_delete';
    public const PUBLISH = 'sport_event_publish';
    public const CLOSE   = 'sport_event_close';
    public const CANCEL  = 'sport_event_cancel';
    public const RESULT  = 'sport_event_result';

    private const ATTRIBUTES = [
        self::EDIT,
        self::DELETE,
        self::PUBLISH,
        self::CLOSE,
        self::CANCEL,
        self::RESULT,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof SportEvent;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var SportEvent $event */
        $event = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if ($event->getManager()?->getId() !== $user->getId()) {
            return false;
        }

        return match ($attribute) {
            self::EDIT,
            self::DELETE,
            self::PUBLISH  => $event->getStatus() === SportEvent::STATUS_BROUILLON,
            self::CLOSE    => $event->getStatus() === SportEvent::STATUS_PUBLIE,
            self::CANCEL   => in_array($event->getStatus(), [SportEvent::STATUS_PUBLIE, SportEvent::STATUS_FERME], true),
            self::RESULT   => $event->getStatus() === SportEvent::STATUS_FERME,
            default        => false,
        };
    }
}
