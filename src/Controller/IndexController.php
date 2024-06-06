<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnnonceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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

    #[Route('/design', name: 'design')]
    public function designAction(Request $request): Response
    {
        return $this->render('index/design.twig', ['user' => $this->getUser()]);
    }

    /** Testing mail send */
    #[Route('/mail', name: 'mail')]
    public function mail(MailerInterface $mailer)
    {
        $email = (new Email())
            ->from('gestion@eveoniris.com')
            ->to('gectou4@gmail.com')
            // ->cc('cc@example.com')
            // ->bcc('bcc@example.com')
            // ->replyTo('fabien@example.com')
            // ->priority(Email::PRIORITY_HIGH)
            // -> htmlTemplate('emails/test.twig')
            // ->context([]) // twig parameter
            ->subject('Test')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        try {
            $mailer->send($email);
                echo 'ok';

        } catch (\Exception $e) {
            dump($e);
            echo 'KO';
        }

        dd('test');
    }

    #[Route('/setpwd', name: 'setpwd')]
    public function pwd(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Only in dev
        if ('dev' !== $this->getParameter('kernel.environment')) {
            $this->addFlash('error', 'Do not challenge the dark gods');

            return $this->redirectToRoute('homepage');
        }

        $userId = $request->get('userid');
        if (!$userId) {
            $this->addFlash('error', 'Missing userid parameter');

            return $this->redirectToRoute('homepage');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found');

            return $this->redirectToRoute('homepage');
        }

        $plaintextPassword = $request->get('pwd');

        if (!$plaintextPassword) {
            $this->addFlash('error', 'Missing pwd parameter');

            return $this->redirectToRoute('homepage');
        }

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        $roles = explode(',', $request->get('roles', 'ROLE_ADMIN,ROLE_USER,ROLE_ORGA'));
        $user->setRoles($roles);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($user);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        $this->addFlash('success', 'Password & Role updated for user '.$userId);

        return $this->redirectToRoute('homepage');
    }
}
