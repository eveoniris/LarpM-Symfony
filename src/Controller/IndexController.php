<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\AnnonceRepository;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class IndexController extends AbstractController
{
    /**
     * Guide de style : réservé aux rôles qui construisent le contenu du site
     * (tout utilisateur connecté sans rôle en est exclu). ROLE_ADMIN hérite des autres.
     */
    #[Route('/design', name: 'design')]
    #[IsGranted(new MultiRolesExpression(Role::ADMIN, Role::SCENARISTE, Role::ORGA, Role::REGLE, Role::STOCK))]
    public function designAction(Request $request): Response
    {
        return $this->render('index/design.twig', ['user' => $this->getUser()]);
    }

    #[Route('/', name: 'homepage')]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'user' => $user,
            'allowRememberMe' => true,
            'participants' => $user?->getParticipants(),
            'annonces' => $annonceRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    /** Testing mail send */
    #[Route('/mail', name: 'mail')]
    public function mail(MailerInterface $mailer): Response
    {
        // DETECT ENV : dump($_SERVER['APP_ENV']);
        // OR DETECT ENV : dump($this->getParameter('kernel.environment'));

        // Only in dev
        if ('dev' !== $this->getParameter('kernel.environment')) {
            $this->addFlash('error', 'Do not challenge the dark gods');

            return $this->redirectToRoute('homepage');
        }

        $email = (new Email())
            ->from('gestion@eveoniris.com')
            ->to('gectou4@eveoniris.com')
            // ->cc('cc@example.com')
            // ->bcc('bcc@example.com')
            // ->replyTo('fabien@example.com')
            // ->priority(Email::PRIORITY_HIGH)
            // -> htmlTemplate('emails/test.twig')
            // ->context([]) // twig parameter
            ->subject('Test')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $mailer->send($email);

        return $this->redirectToRoute('homepage');
    }

    #[Route('/setpwd', name: 'setpwd')]
    public function pwd(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        // Only in dev
        if ('dev' !== $this->getParameter('kernel.environment')) {
            $this->addFlash('error', 'Do not challenge the dark gods');

            return $this->redirectToRoute('homepage');
        }

        $userId = $request->query->get('userid');
        if (!$userId) {
            $this->addFlash('error', 'Missing userid parameter');

            return $this->redirectToRoute('homepage');
        }

        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found');

            return $this->redirectToRoute('homepage');
        }

        $plaintextPassword = $request->query->get('pwd');

        if (!$plaintextPassword) {
            $this->addFlash('error', 'Missing pwd parameter');

            return $this->redirectToRoute('homepage');
        }

        // hash the password (based on the security.yaml config for the $user class)
        $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);

        $roles = explode(',', $request->query->get('roles', 'ROLE_ADMIN,ROLE_USER,ROLE_ORGA'));
        $user->setRoles($roles);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($user);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        $this->addFlash('success', 'Password & Role updated for user ' . $userId);

        return $this->redirectToRoute('homepage');
    }
}
