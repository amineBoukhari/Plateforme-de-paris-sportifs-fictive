<?php

namespace App\Controller\Api;

use App\Repository\SportEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends AbstractController
{
    // US-70 — GET /api/events
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(SportEventRepository $repo): JsonResponse
    {
        $events = $repo->findPublished();

        $data = array_map(fn($event) => $this->serializeEvent($event), $events);

        return $this->json($data);
    }

    // US-71 — GET /api/events/{id}
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, SportEventRepository $repo): JsonResponse
    {
        $event = $repo->find($id);

        if (!$event || $event->getStatus() !== \App\Entity\SportEvent::STATUS_PUBLIE) {
            return $this->json(['error' => 'Événement introuvable.'], 404);
        }

        return $this->json($this->serializeEvent($event, true));
    }

    private function serializeEvent(\App\Entity\SportEvent $event, bool $withOutcomes = false): array
    {
        $data = [
            'id'           => $event->getId(),
            'name'         => $event->getName(),
            'sport'        => $event->getSport(),
            'participants' => $event->getParticipants(),
            'eventDate'    => $event->getEventDate()?->format(\DateTimeInterface::ATOM),
            'status'       => $event->getStatus(),
        ];

        if ($withOutcomes) {
            $data['outcomes'] = $event->getOutcomes()->map(fn($o) => [
                'id'    => $o->getId(),
                'label' => $o->getLabel(),
                'odds'  => (float) $o->getOdds(),
            ])->toArray();
        }

        return $data;
    }
}
