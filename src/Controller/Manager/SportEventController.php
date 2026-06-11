<?php

namespace App\Controller\Manager;

use App\Entity\Outcome;
use App\Entity\SportEvent;
use App\Form\SportEventType;
use App\Repository\OutcomeRepository;
use App\Repository\SportEventRepository;
use App\Security\Voter\SportEventVoter;
use App\Service\PayoutService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/manager/events', name: 'app_manager_event_')]
#[IsGranted('ROLE_MANAGER')]
class SportEventController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, SportEventRepository $repo, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $repo->createByManagerQueryBuilder($this->getUser()->getId()),
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('manager/sport_event/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new SportEvent();
        $form  = $this->createForm(SportEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setManager($this->getUser());
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Événement créé en brouillon.');
            return $this->redirectToRoute('app_manager_event_index');
        }

        return $this->render('manager/sport_event/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(SportEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(SportEventVoter::EDIT, $event);

        $form = $this->createForm(SportEventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Événement mis à jour.');
            return $this->redirectToRoute('app_manager_event_index');
        }

        return $this->render('manager/sport_event/edit.html.twig', [
            'form'  => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(SportEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(SportEventVoter::DELETE, $event);

        if ($this->isCsrfTokenValid('delete-event-'.$event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }

        return $this->redirectToRoute('app_manager_event_index');
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(SportEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(SportEventVoter::PUBLISH, $event);

        if ($event->getOutcomes()->isEmpty()) {
            $this->addFlash('error', 'Ajoutez au moins une issue avant de publier.');
            return $this->redirectToRoute('app_manager_event_index');
        }

        if ($this->isCsrfTokenValid('publish-event-'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus(SportEvent::STATUS_PUBLIE);
            $em->flush();
            $this->addFlash('success', 'Événement publié — les paris sont ouverts.');
        }

        return $this->redirectToRoute('app_manager_event_index');
    }

    #[Route('/{id}/close', name: 'close', methods: ['POST'])]
    public function close(SportEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(SportEventVoter::CLOSE, $event);

        if ($this->isCsrfTokenValid('close-event-'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus(SportEvent::STATUS_FERME);
            $em->flush();
            $this->addFlash('success', 'Paris fermés. Saisissez maintenant le résultat.');
        }

        return $this->redirectToRoute('app_manager_event_index');
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    public function cancel(SportEvent $event, Request $request, EntityManagerInterface $em, PayoutService $payout): Response
    {
        $this->denyAccessUnlessGranted(SportEventVoter::CANCEL, $event);

        if ($this->isCsrfTokenValid('cancel-event-'.$event->getId(), $request->request->get('_token'))) {
            $event->setStatus(SportEvent::STATUS_ANNULE);
            $em->flush();
            $payout->refund($event);
            $this->addFlash('success', 'Événement annulé — paris remboursés.');
        }

        return $this->redirectToRoute('app_manager_event_index');
    }

    // US-55 + US-56 — Saisir le résultat et déclencher le calcul des gains
    #[Route('/{id}/result', name: 'result')]
    public function result(
        SportEvent      $event,
        Request         $request,
        EntityManagerInterface $em,
        PayoutService   $payout,
        OutcomeRepository $outcomeRepo,
    ): Response {
        $this->denyAccessUnlessGranted(SportEventVoter::RESULT, $event);

        if ($request->isMethod('POST')) {
            $token     = $request->request->get('_token');
            $outcomeId = (int) $request->request->get('outcome_id');

            if (!$this->isCsrfTokenValid('result-event-'.$event->getId(), $token)) {
                $this->addFlash('error', 'Token invalide.');
                return $this->redirectToRoute('app_manager_event_result', ['id' => $event->getId()]);
            }

            $winningOutcome = $outcomeRepo->find($outcomeId);

            if (!$winningOutcome || $winningOutcome->getSportEvent() !== $event) {
                $this->addFlash('error', 'Issue invalide.');
                return $this->redirectToRoute('app_manager_event_result', ['id' => $event->getId()]);
            }

            $winningOutcome->setIsWinner(true);
            $event->setStatus(SportEvent::STATUS_TERMINE);
            $em->flush();

            $payout->payout($event, $winningOutcome);

            $this->addFlash('success', sprintf(
                'Résultat enregistré : "%s". Les gains ont été distribués.',
                $winningOutcome->getLabel()
            ));

            return $this->redirectToRoute('app_manager_event_index');
        }

        return $this->render('manager/sport_event/result.html.twig', [
            'event' => $event,
        ]);
    }
}
