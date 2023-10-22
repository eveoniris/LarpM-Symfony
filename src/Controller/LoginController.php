<?php

namespace App\Controller;

use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/loginZ', name: 'app_login_formXX')]
    public function index(
        AuthenticationUtils $authenticationUtils,
        UserPasswordHasherInterface $passwordHasher,
        ManagerRegistry $doctrine
    ): Response {
        $em = $doctrine->getRepository(User::class);
        $u = $em->find(3265);
        dump($u);

        $hashedPassword = $passwordHasher->hashPassword(
            $u,
            'sierralima'
        );

        dump($hashedPassword);
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'allowRememberMe' => true, // TODO
        ]);
    }
}
