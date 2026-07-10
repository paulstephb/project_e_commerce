<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor')]
    public function changeRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(['ROLE_EDITOR',  'ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', 'Editor role assigned successfully.');
        return $this->redirectToRoute('app_user');
    }

     #[Route('/admin/user/{id}/remove/editor/role', name: 'app_user_remove_editor_role')]
    public function removeRoleEditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('success', 'Editor role removed successfully.');
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/delete', name: 'app_user_delete')]
    public function deleteUser(EntityManagerInterface $entityManager, User $user): Response
    {
        $entityManager->remove($user);
        $entityManager->flush(); 

        $this->addFlash('success', 'User deleted successfully.');
        return $this->redirectToRoute('app_user');
    }
}