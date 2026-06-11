<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'app_admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $repo): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $repo->findBy([], ['email' => 'ASC']),
        ]);
    }

    // US-62 — Suspendre un compte
    #[Route('/{id}/suspend', name: 'suspend', methods: ['POST'])]
    public function suspend(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas suspendre votre propre compte.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($this->isCsrfTokenValid('suspend-user-'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(false);
            $em->flush();
            $this->addFlash('success', sprintf('Compte %s suspendu.', $user->getEmail()));
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    // US-63 — Réactiver un compte
    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('activate-user-'.$user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(true);
            $em->flush();
            $this->addFlash('success', sprintf('Compte %s réactivé.', $user->getEmail()));
        }

        return $this->redirectToRoute('app_admin_user_index');
    }

    // US-61 — Attribuer / retirer ROLE_MANAGER
    #[Route('/{id}/toggle-manager', name: 'toggle_manager', methods: ['POST'])]
    public function toggleManager(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre rôle.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        if ($this->isCsrfTokenValid('toggle-manager-'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();
            if (in_array('ROLE_MANAGER', $roles, true)) {
                $user->setRoles(array_values(array_filter($roles, fn($r) => $r !== 'ROLE_MANAGER' && $r !== 'ROLE_USER')));
                $this->addFlash('success', sprintf('Rôle Manager retiré à %s.', $user->getEmail()));
            } else {
                $roles[] = 'ROLE_MANAGER';
                $user->setRoles(array_values(array_unique(array_filter($roles, fn($r) => $r !== 'ROLE_USER'))));
                $this->addFlash('success', sprintf('Rôle Manager attribué à %s.', $user->getEmail()));
            }
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
