<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Form\Type\TagType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STOCK')]
class StockTagController extends AbstractController
{
    #[Route('/stock/tag', name: 'stockTag.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Tag::class);
        $tags = $repo->findAll();

        return $this->render('stock/tag/index.twig', ['tags' => $tags]);
    }

    #[Route('/stock/tag/add', name: 'stockTag.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $tag = new Tag();

        $form = $this->createForm(TagType::class, $tag)
            ->add('save', SubmitType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tag = $form->getData();
            $entityManager->persist($tag);
            $entityManager->flush();

            $this->addFlash('success', 'Le tag a été ajouté.');

            return $this->redirectToRoute('stockTag.index');
        }

        return $this->render('stock/tag/add.twig', ['form' => $form->createView()]);
    }

    #[Route('/stock/tag/{tag}', name: 'stockTag.update')]
    public function updateAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Tag $tag): RedirectResponse|Response
    {
        $form = $this->createForm(TagType::class, $tag)
            ->add('update', SubmitType::class)
            ->add('delete', SubmitType::class);

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

            return $this->redirectToRoute('stockTag.index');
        }

        return $this->render('stock/tag/update.twig', [
            'tag' => $tag,
            'form' => $form->createView()]);
    }
}
