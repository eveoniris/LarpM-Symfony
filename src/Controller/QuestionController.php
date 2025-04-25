<?php

namespace App\Controller;

use App\Entity\Question;
use App\Enum\Role;
use App\Form\Question\QuestionDeleteForm;
use App\Form\Question\QuestionForm;
use App\Repository\QuestionRepository;
use App\Security\MultiRolesExpression;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class QuestionController extends AbstractController
{
    /**
     * Ajout d'une question.
     */
    #[Route('/question/add', name: 'question.add')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $form = $this->createForm(QuestionForm::class, new Question());
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

        return $this->render('question\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'une question.
     */
    #[Route('/question/{question}/delete', name: 'question.delete')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Question $question,
    ): RedirectResponse|Response {
        $form = $this->createForm(QuestionDeleteForm::class, $question)
            ->add('submit', SubmitType::class, ['label' => 'Supprimer']);

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

        return $this->render('question\delete.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }

    /**
     * Détail d'une question.
     */
    #[Route('/question/{question}/detail', name: 'question.detail')]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Question $question,
    ): Response {
        return $this->render('question\detail.twig', [
            'question' => $question,
        ]);
    }

    /**
     * Liste des question.
     */
    #[Route('/question', name: 'question')]
    public function indexAction(Request $request, QuestionRepository $repository): Response
    {
        $questions = $repository->findAll();

        return $this->render(
            'question\index.twig',
            ['questions' => $questions],
        );
    }

    /**
     * Mise à jour d'une question.
     */
    #[Route('/question/{question}/update', name: 'question.update')]
    #[IsGranted(new MultiRolesExpression(Role::ORGA))]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Question $question,
    ): RedirectResponse|Response {
        $form = $this->createForm(QuestionForm::class, $question);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question = $form->getData();

            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'La question a été mise à jour.');

            return $this->redirectToRoute('question', [], 303);
        }

        return $this->render('question\update.twig', [
            'form' => $form->createView(),
            'question' => $question,
        ]);
    }
}
