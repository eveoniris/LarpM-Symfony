<?php


namespace App\Controller;

use App\Form\AttributeTypeForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]class AttributeTypeController extends AbstractController
{
    /**
     * Liste des types d'attribut.
     */
    #[Route('/attributeType', name: 'attributeType.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\AttributeType::class);
        $attributes = $repo->findAllOrderedByLabel();

        return $this->render('admin/attributeType/index.twig', ['attributes' => $attributes]);
    }

    /**
     * Ajoute d'un attribut.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $attributeType = new \App\Entity\AttributeType();

        $form = $this->createForm(AttributeTypeForm::class, $attributeType)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attributeType = $form->getData();

            $entityManager->persist($attributeType);
            $entityManager->flush();

           $this->addFlash('success', 'Le type d\'attribut a été ajoutée.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('attribute.type', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('attribute.type.add', [], 303);
            }
        }

        return $this->render('admin/attributeType/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour un attribut.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $attributeType = $entityManager->find('\\'.\App\Entity\AttributeType::class, $id);

        $form = $this->createForm(AttributeTypeForm::class, $attributeType)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attributeType = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($attributeType);
                $entityManager->flush();
               $this->addFlash('success', 'La type d\'attribut a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($attributeType);
                $entityManager->flush();
               $this->addFlash('success', 'Le type d\'attribut a été supprimé.');
            }

            return $this->redirectToRoute('attribute.type');
        }

        return $this->render('admin/attributeType/update.twig', [
            'attributeType' => $attributeType,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un attribut.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $attributeType = $entityManager->find('\\'.\App\Entity\AttributeType::class, $id);

        if ($attributeType) {
            return $this->render('admin/attributeType/detail.twig', ['attributeType' => $attributeType]);
        } else {
           $this->addFlash('error', 'La attribute type n\'a pas été trouvé.');

            return $this->redirectToRoute('attribute.type');
        }
    }
}
