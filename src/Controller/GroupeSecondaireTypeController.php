<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Role;
use App\Form\Groupe\GroupeSecondaireTypeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    #[IsGranted(Role::ROLE_GROUPE_TRANSVERSE->value)]
    public function adminAddAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $groupeSecondaireType = new \App\Entity\SecondaryGroupType();

        $form = $this->createForm(GroupeSecondaireTypeType::class, $groupeSecondaireType)->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
            'label' => 'Sauvegarder',
        ])->add('save_continue', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, [
            'label' => 'Sauvegarder & continuer',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeSecondaireType = $form->getData();

            $entityManager->persist($groupeSecondaireType);
            $entityManager->flush();

            $this->addFlash('success', 'Le type de groupe transverse a été ajouté.');

            if ($form->get('save') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaire.list', [], 303);
            } elseif ($form->get('save_continue') instanceof \Symfony\Component\Form\ClickableInterface && $form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('groupeSecondaireType.add', [], 303);
            }
        }

        return $this->render('groupeSecondaireType/add.twig', [
            'form' => $form->createView(),
        ]);
    }
}
