<?php


namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockTagController extends AbstractController
{
    /**
     * Liste des tags.
     */
    #[Route('/stock/tag', name: 'stockTag.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\Tag::class);
        $tags = $repo->findAll();

        return $this->render('stock/tag/index.twig', ['tags' => $tags]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $tag = new \App\Entity\Tag();

        $form = $this->createForm(TagType::class(), $tag)
            ->add('save', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tag = $form->getData();
            $entityManager->persist($tag);
            $entityManager->flush();

           $this->addFlash('success', 'Le tag a été ajouté.');

            return $this->redirectToRoute('stock_tag_index');
        }

        return $this->render('stock/tag/add.twig', ['form' => $form->createView()]);
    }

    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $repo = $entityManager->getRepository('\\'.\App\Entity\Tag::class);
        $tag = $repo->find($id);

        $form = $this->createForm(TagType::class(), $tag)
            ->add('update', 'submit')
            ->add('delete', 'submit');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tag = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($tag);
                $entityManager->flush();
               $this->addFlash('success', 'Le tag a été modifié.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($tag);
                $entityManager->flush();
               $this->addFlash('success', 'Le tag a été supprimé.');
            }

            return $this->redirectToRoute('stock_tag_index');
        }

        return $this->render('stock/tag/update.twig', [
            'tag' => $tag,
            'form' => $form->createView()]);
    }
}
