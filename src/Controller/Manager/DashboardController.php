<?php

namespace App\Controller\Manager;

use App\Entity\SportEvent;
use App\Repository\SportEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager', name: 'app_manager_')]
#[IsGranted('ROLE_MANAGER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(SportEventRepository $repo): Response
    {
        $events = $repo->findBy(['manager' => $this->getUser()], ['eventDate' => 'DESC']);

        $counts = [
            'total'     => count($events),
            'brouillon' => count(array_filter($events, fn($e) => $e->getStatus() === SportEvent::STATUS_BROUILLON)),
            'publie'    => count(array_filter($events, fn($e) => $e->getStatus() === SportEvent::STATUS_PUBLIE)),
            'ferme'     => count(array_filter($events, fn($e) => $e->getStatus() === SportEvent::STATUS_FERME)),
        ];

        return $this->render('manager/dashboard.html.twig', [
            'counts' => $counts,
            'recent' => array_slice($events, 0, 5),
        ]);
    }
}
