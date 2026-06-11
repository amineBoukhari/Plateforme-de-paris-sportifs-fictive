<?php

namespace App\Controller\User;

use App\Service\WalletService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/wallet', name: 'app_user_wallet_')]
#[IsGranted('ROLE_USER')]
class WalletController extends AbstractController
{
    #[Route('/deposit', name: 'deposit')]
    public function deposit(Request $request, WalletService $wallet): Response
    {
        if ($request->isMethod('POST')) {
            $token  = $request->request->get('_token');
            $amount = (float) $request->request->get('amount', 0);

            if (!$this->isCsrfTokenValid('wallet_deposit', $token)) {
                $this->addFlash('error', 'Token invalide.');
                return $this->redirectToRoute('app_user_wallet_deposit');
            }

            try {
                $wallet->deposit($this->getUser(), $amount);
                $this->addFlash('success', number_format($amount, 2, ',', ' ') . ' € crédités sur votre solde.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_user_wallet_deposit');
        }

        return $this->render('user/wallet/deposit.html.twig');
    }
}
