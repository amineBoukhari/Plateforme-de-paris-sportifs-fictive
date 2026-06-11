<?php

namespace App\Controller\User;

use App\Entity\SportEvent;
use App\Repository\BetRepository;
use App\Repository\OutcomeRepository;
use App\Repository\SportEventRepository;
use App\Service\BettingService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/bets', name: 'app_user_bet_')]
#[IsGranted('ROLE_USER')]
class BetController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(SportEventRepository $repo): Response
    {
        return $this->render('user/bet/index.html.twig', [
            'events' => $repo->findPublished(),
        ]);
    }

    #[Route('/event/{id}', name: 'show')]
    public function show(SportEvent $event): Response
    {
        if ($event->getStatus() !== SportEvent::STATUS_PUBLIE) {
            $this->addFlash('error', 'Cet événement n\'est plus ouvert aux paris.');
            return $this->redirectToRoute('app_user_bet_index');
        }

        return $this->render('user/bet/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/place', name: 'place', methods: ['POST'])]
    public function place(Request $request, BettingService $betting, OutcomeRepository $outcomes): Response
    {
        $token     = $request->request->get('_token');
        $outcomeId = (int) $request->request->get('outcome_id');
        $amount    = (float) $request->request->get('amount', 0);
        $eventId   = (int) $request->request->get('event_id');

        if (!$this->isCsrfTokenValid('bet_place', $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_user_bet_index');
        }

        $outcome = $outcomes->find($outcomeId);

        if (!$outcome) {
            $this->addFlash('error', 'Issue introuvable.');
            return $this->redirectToRoute('app_user_bet_index');
        }

        try {
            $bet = $betting->place($this->getUser(), $outcome, $amount);
            $this->addFlash('success', sprintf(
                'Pari de %.2f € placé sur "%s" à la cote %.2f. Gain potentiel : %.2f €.',
                $amount,
                $outcome->getLabel(),
                (float) $bet->getOddsAtBet(),
                $amount * (float) $bet->getOddsAtBet()
            ));
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_user_bet_show', ['id' => $outcome->getSportEvent()->getId()]);
    }

    #[Route('/history', name: 'history')]
    public function history(Request $request, BetRepository $betRepository, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $betRepository->createByUserQueryBuilder($this->getUser()->getId()),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('user/bet/history.html.twig', [
            'pagination' => $pagination,
        ]);
    }
}
