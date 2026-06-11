<?php

namespace App\Controller\User;

use App\Service\ResponsibleGamingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/responsible', name: 'app_user_responsible_')]
#[IsGranted('ROLE_USER')]
class ResponsibleGamingController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ResponsibleGamingService $rgs): Response
    {
        $user = $this->getUser();
        $rgs->applyPendingIncreases($user);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if (!$this->isCsrfTokenValid('responsible_gaming', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token invalide.');
                return $this->redirectToRoute('app_user_responsible_index');
            }

            try {
                if ($action === 'limits') {
                    $fields = ['depositDaily', 'depositWeekly', 'betDaily', 'betWeekly'];
                    foreach ($fields as $field) {
                        $raw = $request->request->get($field);
                        $value = ($raw !== null && $raw !== '') ? (float) $raw : null;
                        $rgs->setLimit($user, $field, $value);
                    }
                    $this->addFlash('success', 'Plafonds mis à jour. Les augmentations seront effectives dans 48h.');
                }

                if ($action === 'self_exclude') {
                    $endDateStr = $request->request->get('end_date');
                    if (!$endDateStr) {
                        throw new \LogicException('Veuillez choisir une date de fin.');
                    }
                    $endDate = new \DateTimeImmutable($endDateStr);
                    $rgs->selfExclude($user, $endDate);
                    $this->addFlash('success', 'Auto-exclusion activée. Vous allez être déconnecté.');
                    return $this->redirectToRoute('app_logout');
                }
            } catch (\LogicException|\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_user_responsible_index');
        }

        return $this->render('user/responsible/index.html.twig', [
            'config'  => $user->getLimitConfig(),
            'pending' => $user->getLimitConfig()?->getPendingIncrease() ?? [],
        ]);
    }
}
