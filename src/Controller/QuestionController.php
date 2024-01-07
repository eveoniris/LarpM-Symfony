<?php


namespace App\Controller;

use App\Entity\Question;
use App\Form\Question\QuestionDeleteForm;
use App\Form\Question\QuestionForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    /**
     * Liste des question.
     */
    #[Route('/question', name: 'question')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $questions = $entityManager->getRepository(\App\Entity\Question::class)->findAll();

        return $this->render('admin\question\index.twig', [
            'questions' => $questions,
        ]);
    }

    /**
     * Ajout d'une question.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(QuestionForm::class, new Question())->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question = $form->getData();
            $question->setUser($this->getUser());
            $question->setDate(new \DateTime('NOW'));

            $entityManager->persist($question);
            $entityManager->flush();

           $this->addFlash('success', 'La question a été ajoutée.');

            return $this->redirectToRoute('question', [], 303);
        }

        return $this->render('admin\question\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une question.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Question $question)
    {
        return $this->render('admin\question\detail.twig', [
            'question' => $question,
        ]);
    }

    /**
     * Mise à jour d'une question.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Question $question)
    {
        $form = $this->createForm(QuestionForm::class, $question);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question = $form->getData();

            $entityManager->persist($question);
            $entityManager->flush();

           $this->addFlash('success', 'La question a été mise à jour.');

            return $this->redirectToRoute('question', [], 303);
        }

        return $this->render('admin\question\update.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }

    /**
     * Suppression d'une question.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Question $question)
    {
        $form = $this->createForm(QuestionDeleteForm::class, $question)
            ->add('submit', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question = $form->getData();

            foreach ($question->getReponses() as $reponse) {
                $entityManager->remove($reponse);
            }

            $entityManager->remove($question);
            $entityManager->flush();

           $this->addFlash('success', 'La question a été supprimée.');

            return $this->redirectToRoute('question', [], 303);
        }

        return $this->render('admin\question\delete.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }
}
