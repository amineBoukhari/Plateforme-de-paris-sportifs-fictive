<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/user')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_user_dashboard')]
    public function index(): Response
    {
        return $this->render('user/dashboard.html.twig');
    }
}
