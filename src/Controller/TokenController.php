<?php


namespace App\Controller;

use App\Entity\Token;
use App\Form\TokenDeleteForm;
use App\Form\TokenForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class TokenController extends AbstractController
{
    /**
     * Liste des tokens.
     */
    #[Route('/token', name: 'token.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $tokens = $entityManager->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        return $this->render('token/list.twig', ['tokens' => $tokens]);
    }

    /**
     * Impression des tokens.
     */
    public function printAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $tokens = $entityManager->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        return $this->render('token/print.twig', ['tokens' => $tokens]);
    }

    /**
     * Téléchargement des tokens.
     */
    public function downloadAction(Request $request,  EntityManagerInterface $entityManager): void
    {
        $tokens = $entityManager->getRepository('\\'.\App\Entity\Token::class)->findAllOrderedByLabel();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_tokens_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'id',
                'label',
                'tag',
                'description'], ';');

        foreach ($tokens as $token) {
            fputcsv($output, $token->getExportValue(), ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Ajouter un token.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $token = new Token();

        $form = $this->createForm(TokenForm::class, $token)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $form->getData();

            $entityManager->persist($token);
            $entityManager->flush($token);

           $this->addFlash('success', 'Le jeton a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('token.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('token.add', [], 303);
            }
        }

        return $this->render('token/add.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'un token.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Token $token)
    {
        return $this->render('token/detail.twig', ['token' => $token]);
    }

    /**
     * Mise à jour d'un token.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Token $token)
    {
        $form = $this->createForm(TokenForm::class, $token)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $token = $form->getData();

            $entityManager->persist($token);
            $entityManager->flush($token);

           $this->addFlash('success', 'Le jeton a été modifié.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $this->render('token/update.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un token.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Token $token)
    {
        $form = $this->createForm(TokenDeleteForm::class, $token)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer', 'attr' => ['class' => 'btn-danger']]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->remove($token);
            $entityManager->flush($token);

           $this->addFlash('success', 'Le jeton a été supprimé.');

            return $this->redirectToRoute('token.list', [], 303);
        }

        return $this->render('token/delete.twig', [
            'token' => $token,
            'form' => $form->createView(),
        ]);
    }
}
