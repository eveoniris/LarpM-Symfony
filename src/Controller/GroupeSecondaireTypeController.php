<?php


namespace App\Controller;

use App\Form\Groupe\GroupeSecondaireTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\GroupeSecondaireTypeController.
 *
 * @author kevin
 */
class GroupeSecondaireTypeController extends AbstractController
{
    /**
     * Ajoute un type de groupe secondaire.
     */
    #[Route('/groupeSecondaireType/add', name: 'groupeSecondaireType.add')]
    public function adminAddAction(Request $request, EntityManagerInterface $entityManager)
    {
        $groupeSecondaireType = new \App\Entity\SecondaryGroupType();

        $form = $this->createForm(GroupeSecondaireTypeForm::class, $groupeSecondaireType)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add(
                'save_continue',
                \Symfony\Component\Form\Extension\Core\Type\SubmitType::class,
                ['label' => 'Sauvegarder & continuer'],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaireType = $form->getData();

            $entityManager->persist($groupeSecondaireType);
            $entityManager->flush();

            $this->addFlash('success', 'Le type de groupe transverse a été ajouté.');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaireType.add', [], 303);
            }
        }

        return $this->render('groupeSecondaireType/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Detail d'un type de groupe secondaire.
     */
    public function adminDetailAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $groupeSecondaireType = $entityManager->find('\\'.\App\Entity\SecondaryGroupType::class, $id);

        if ($groupeSecondaireType) {
            return $this->render('groupeSecondaireType/detail.twig', ['groupeSecondaireType' => $groupeSecondaireType]);
        } else {
            $this->addFlash('error', 'Le type de groupe transverse n\'a pas été trouvé.');

            return $this->redirectToRoute('groupeSecondaireType.list');
        }
    }

    /**
     * Liste les types de groupe secondaire.
     */
    public function adminListAction(Request $request, EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\\'.\App\Entity\SecondaryGroupType::class);
        $groupeSecondaireTypes = $repo->findAll();

        return $this->render('groupeSecondaireType/list.twig', ['groupeSecondaireTypes' => $groupeSecondaireTypes]);
    }

    /**
     * Met à jour un type de groupe secondaire.
     */
    public function adminUpdateAction(Request $request, EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $groupeSecondaireType = $entityManager->find('\\'.\App\Entity\SecondaryGroupType::class, $id);

        $form = $this->createForm(GroupeSecondaireTypeForm::class, $groupeSecondaireType)
            ->add('update', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaireType = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($groupeSecondaireType);
                $entityManager->flush();
                $this->addFlash('success', 'Le type de groupe transverse a été mis à jour.');
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($groupeSecondaireType);
                $entityManager->flush();
                $this->addFlash('success', 'Le type de groupe transverse a été supprimé.');
            }

            return $this->redirectToRoute('groupeSecondaireType.list');
        }

        return $this->render('groupeSecondaireType/update.twig', [
            'groupeSecondaireType' => $groupeSecondaireType,
            'form' => $form->createView(),
        ]);
    }
}
