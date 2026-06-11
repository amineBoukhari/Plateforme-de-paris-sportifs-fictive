<?php

namespace App\Controller\Admin;

use App\Repository\BetRepository;
use App\Repository\SportEventRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function index(
        UserRepository        $users,
        BetRepository         $bets,
        SportEventRepository  $events,
        TransactionRepository $transactions,
    ): Response {
        $allBets = $bets->findAll();

        $stats = [
            'total_users'        => count($users->findAll()),
            'active_users'       => count($users->findActiveUsers()),
            'total_events'       => count($events->findAll()),
            'published_events'   => count($events->findPublished()),
            'total_bets'         => count($allBets),
            'bets_pending'       => count(array_filter($allBets, fn($b) => $b->getStatus() === 'EN_ATTENTE')),
            'bets_won'           => count(array_filter($allBets, fn($b) => $b->getStatus() === 'GAGNE')),
            'total_wagered'      => array_sum(array_map(fn($b) => (float) $b->getAmount(), $allBets)),
        ];

        $recentBets = $bets->findBy([], ['createdAt' => 'DESC'], 20);

        return $this->render('admin/dashboard.html.twig', [
            'stats'      => $stats,
            'recentBets' => $recentBets,
        ]);
    }
}
