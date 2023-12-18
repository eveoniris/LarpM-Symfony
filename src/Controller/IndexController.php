<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnnonceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Request $request, AnnonceRepository $annonceRepository): Response
    {
         return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'user' => $this->getUser(),
            'annonces' => $annonceRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    /**
     * TODO : remove asap.
     */
    #[Route('/setpwd', name: 'setpwd')]
    public function pwd(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $user = $entityManager->getRepository(User::class)->find($request->get('userid'));

        $plaintextPassword = $request->get('pwd');

        if (!$plaintextPassword) {
            return $this->redirectToRoute('homepage');
        }

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ORGA']);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($user);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
        // ...

        return $this->redirectToRoute('homepage');
    }
}
