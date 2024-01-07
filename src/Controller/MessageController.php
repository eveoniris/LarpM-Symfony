<?php


namespace App\Controller;

use App\Entity\Message;
use App\Repository\BaseRepository;
use App\Form\NewMessageForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    /**
     * Affiche la messagerie de l'utilisateur.
     */
    #[Route('/messagerie', name: 'messagerie')]
    public function messagerieAction(Request $request)
    {
        return $this->render('message/messagerie.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages archiver de l'utilisateur.
     */
    #[Route('/messagerie/archive', name: 'message.archives')]
    public function archiveAction(Request $request)
    {
        return $this->render('message/archive.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Affiche les messages envoyé par l'utilisateur.
     */
    #[Route('/messagerie/envoye', name: 'message.envoye')]
    public function envoyeAction(Request $request)
    {
        return $this->render('message/envoye.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Nouveau message.
     */
    #[Route('/messagerie/new', name: 'message.new')]
    public function newAction(Request $request)
    {
        $message = new Message();
        $message->setUserRelatedByAuteur($this->getUser());

        $to_id = $request->get('to');
        if ($to_id) {
            $to = $app['converter.user']->convert($to_id);
            $message->setUserRelatedByDestinataire($to);
        }

        $form = $this->createForm(NewMessageForm::class, $message)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre message']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            // ajout de la signature
            $personnage = $this->getUser()->getPersonnage();
            if ($personnage) {
                $text = $message->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $message->setText($text);
            }

            $entityManager->persist($message);
            $entityManager->flush();

            // création de la notification
            $destinataire = $message->getUserRelatedByDestinataire();
            $app['notify']->newMessage($destinataire, $message);

           $this->addFlash('success', 'Votre message a été envoyé.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('public/message/new.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Archiver un message.
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedException
     */
    #[Route('/messagerie/message-archive', name: 'message.archive')]
    public function messageArchiveAction( EntityManagerInterface $entityManager, Request $request, Message $message): bool
    {
        if ($message->getUserRelatedByDestinataire() != $this->getUser()) {
            return false;
        }

        $message->setLu(true);
        $entityManager->persist($message);
        $entityManager->flush();

        return true;
    }

    /**
     * Répondre à un message.
     */
    #[Route('/messagerie/response', name: 'message.response')]
    public function messageResponseAction( EntityManagerInterface $entityManager, Request $request, Message $message)
    {
        $reponse = new \App\Entity\Message();

        $reponse->setUserRelatedByAuteur($this->getUser());
        $reponse->setUserRelatedByDestinataire($message->getUserRelatedByAuteur());
        $reponse->setTitle('Réponse à "'.$message->getTitle().'"');
        $reponse->setCreationDate(new \DateTime('NOW'));
        $reponse->setUpdateDate(new \DateTime('NOW'));

        $form = $this->createForm(NewMessageForm::class, $reponse)
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer votre message']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();

            // ajout de la signature
            $personnage = $this->getUser()->getPersonnageRelatedByPersonnageId();
            if ($personnage) {
                $text = $message->getText();
                $text .= '<address><strong>Envoyé par</strong><br />'.$personnage->getNom().' '.$personnage->getSurnom().'<address>';
                $message->setText($text);
            }

            $entityManager->persist($message);
            $entityManager->flush();

            // création de la notification
            $destinataire = $message->getUserRelatedByDestinataire();
            $app['notify']->newMessage($destinataire, $message);

           $this->addFlash('success', 'Votre message a été envoyé.');

            return $this->redirectToRoute('homepage', [], 303);
        }

        return $this->render('public/message/response.twig', [
            'message' => $message,
            'User' => $this->getUser(),
            'form' => $form->createView(),
        ]);
    }
}
