<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\NewMessageForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    /**
     * Affiche la messagerie de l'utilisateur.
     */
    #[Route('/messagerie', name: 'messagerie')]
    public function messagerieAction(): Response
    {
        return $this->render('message/messagerie.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages archiver de l'utilisateur.
     */
    #[Route('/messagerie/archive', name: 'message.archives')]
    public function archiveAction(Request $request): Response
    {
        return $this->render('message/archive.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages envoyé par l'utilisateur.
     */
    #[Route('/messagerie/envoye', name: 'message.envoye')]
    public function envoyeAction(Request $request): Response
    {
        return $this->render('message/envoye.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Nouveau message.
     */
    #[Route('/messagerie/new', name: 'message.new')]
    public function newAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $message = new Message();
        $message->setUserRelatedByAuteur($this->getUser());

        $to_id = $request->get('to');
        if ($to_id) {
            $userRepository = $entityManager->getRepository(User::class);
            if ($to = $userRepository->find($to_id)) {
                $message->setUserRelatedByDestinataire($to);
            } else {
                $this->addFlash('warning', 'Impossible de trouvé le destinataire');
            }
        }

        $form = $this->handleForm($message, $request, $entityManager);

        if ($form instanceof RedirectResponse) {
            return $form;
        }

        return $this->render('message/new.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Archiver un message.
     */
    #[Route('/messagerie/{message}/message-archive', name: 'message.archive')]
    public function messageArchiveAction(EntityManagerInterface $entityManager, #[MapEntity] Message $message): Response
    {
        if ($message->getUserRelatedByDestinataire() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas archiver ce message.');
        }

        $message->setLu(true);
        $entityManager->persist($message);
        $entityManager->flush();

        $this->addFlash('success', 'Votre message a été archivé.');

        return $this->redirectToRoute('messagerie');
    }

    /**
     * Répondre à un message.
     */
    #[Route('/messagerie/{message}/response', name: 'message.response')]
    public function messageResponseAction(EntityManagerInterface $entityManager, Request $request, #[MapEntity] Message $message): RedirectResponse|Response
    {
        $reponse = new Message();

        $reponse->setUserRelatedByAuteur($this->getUser());
        $reponse->setUserRelatedByDestinataire($message->getUserRelatedByAuteur());
        $reponse->setTitle('Réponse à "'.$message->getTitle().'"');
        $reponse->setCreationDate(new \DateTime('NOW'));
        $reponse->setUpdateDate(new \DateTime('NOW'));

        $form = $this->handleForm($reponse, $request, $entityManager);

        if ($form instanceof RedirectResponse) {
            return $form;
        }

        return $this->render('message/response.twig', [
            'message' => $message,
            'user' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }

    private function addPersonnageToText(Message $message): void
    {
        if ($personnage = $this->getUser()?->getPersonnage()) {
            $message->setText(
                sprintf(
                    '%s <strong>Envoyé par</strong><br />%s %s',
                    $message->getText(),
                    $personnage->getNom(),
                    $personnage->getSurnom()
                )
            );
        }
    }

    private function handleForm(Message $message, $request, EntityManagerInterface $entityManager): RedirectResponse|FormInterface
    {
        $form = $this->createForm(NewMessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre message']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            $this->addPersonnageToText($message);

            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été envoyé.');

            return $this->redirectToRoute('messagerie');
        }

        return $form;
    }
}
