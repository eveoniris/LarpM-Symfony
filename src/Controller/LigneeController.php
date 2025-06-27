<?php

namespace App\Controller;

use App\Entity\Lignee;
use App\Entity\PersonnageLignee;
use App\Form\Lignee\LigneeAddMembreForm;
use App\Form\Lignee\LigneeFindForm;
use App\Form\Lignee\LigneeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class LigneeController extends AbstractController
{
    /**
     * Ajout d'une lignée.
     */
    #[Route('/lignee/add', name: 'lignee.add')]
    public function addAction(
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse|Response {
        $lignee = new Lignee();

        $form = $this->createForm(LigneeForm::class, $lignee)
            ->add(
                'save',
                SubmitType::class,
                ['label' => 'Sauvegarder et retour à la liste'],
            )
            ->add(
                'save_continue',
                SubmitType::class,
                ['label' => 'Sauvegarder et nouvelle lignée'],
            );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lignee = $form->getData();

            $entityManager->persist($lignee);
            $entityManager->flush();
            $this->addFlash('success', 'La lignée a été enregistrée.');

            /*
             * Si l'utilisateur a cliqué sur "save", renvoi vers la liste des lignee
             * Si l'utilisateur a cliqué sur "save_continue", renvoi vers un nouveau formulaire d'ajout
             */
            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('lignee.list', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('lignee.add', [], 303);
            }
        }

        return $this->render('lignee/add.twig', ['form' => $form->createView()]);
    }

    /**
     * Ajoute un nouveau membre à la lignée.
     */
    #[Route('/lignee/{lignee}/addMembre', name: 'lignee.addMembre')]
    public function addMembreAction(
        Request $request,
        #[MapEntity] Lignee $lignee,
    ): RedirectResponse|Response {
        $form = $this->createForm(LigneeAddMembreForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage = $form['personnage']->getData();
            $parent1 = $form['parent1']->getData();
            $parent2 = $form['parent2']->getData();

            $membre = new PersonnageLignee();
            $membre->setPersonnage($personnage);
            $membre->setParent1($parent1);
            $membre->setParent2($parent2);
            $membre->setLignee($lignee);

            $this->entityManager->persist($membre);
            $this->entityManager->flush();

            $this->addFlash('success', 'le personnage a été ajouté à la lignée.');

            return $this->redirectToRoute('lignee.detail', ['lignee' => $lignee->getId()], 303);
        }

        return $this->render('lignee/addMembre.twig', [
            'lignee' => $lignee,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le détail d'une lignée.
     */
    #[Route('/lignee/{lignee}', name: 'lignee.detail')]
    public function detailAction(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity] Lignee $lignee,
    ): RedirectResponse|Response {
        $id = $request->get('lignee');

        $lignee = $entityManager->find(Lignee::class, $id);

        /*
         * Si la lignee existe, on affiche ses détails
         * Sinon on envoie une erreur
         */
        if ($lignee) {
            return $this->render('lignee/details.twig', ['lignee' => $lignee]);
        } else {
            $this->addFlash('error', 'La lignee n\'a pas été trouvée.');

            return $this->redirectToRoute('lignee');
        }
    }

    /**
     * Liste des lignées.
     */
    #[Route('/lignee', name: 'lignee.list')]
    public function listAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $this->createForm(LigneeFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $entityManager->getRepository(Lignee::class);

        $lignees = $repo->findList(
            $type,
            $value,
            $limit,
            $offset,
            ['by' => $order_by, 'dir' => $order_dir],
        );

        $paginator = $repo->findPaginatedQuery(
            $lignees,
            $this->getRequestLimit(),
            $this->getRequestPage(),
        );

        return $this->render('lignee/list.twig', [
            'form' => $form->createView(),
            'paginator' => $paginator,
        ]);
    }

    /**
     * Retire un membre à la lignée.
     */
    #[Route('/lignee/{lignee}/removeMembre', name: 'lignee.removeMembre')]
    public function removeMembreAction(
        Request $request,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $lignee = $request->get('lignee');
        $membreNom = $request->get('membreNom');
        $membre = $request->get('membre');

        $personnageLignee = $entityManager->find(PersonnageLignee::class, $membre);

        $entityManager->remove($personnageLignee);
        $entityManager->flush();

        $this->addFlash('success', $membreNom.' a été retiré de la lignée.');

        return $this->redirectToRoute('lignee.detail', ['lignee' => $lignee, 303]);
    }

    /**
     * Modification d'une lignée.
     */
    #[Route('/lignee/{lignee}/update', name: 'lignee.update')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Lignee $lignee,
    ): RedirectResponse|Response {
        $form = $this->createForm(LigneeForm::class, $lignee)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, [
                'label' => 'Supprimer',
                'attr' => ['onclick' => 'return confirm("Vous vous apprêtez à supprimer cette lignée. Confirmer ?")'],
            ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lignee = $form->getData();

            /*
             * Si l'utilisateur a cliqué sur "update", on met à jour la lignée
             * Si l'utilisateur a cliqué sur "delete", on supprime la lignée
             */
            if ($form->get('update')->isClicked()) {
                $entityManager->persist($lignee);
                $entityManager->flush();
                $this->addFlash('success', 'La lignée a été mise à jour.');

                return $this->redirectToRoute('lignee.detail', ['lignee' => $lignee->getId()]);
            } elseif ($form->get('delete')->isClicked()) {
                // supprime le lien entre les personnages et le groupe
                foreach ($lignee->getPersonnageLignees() as $personnage) {
                    $entityManager->remove($personnage);
                }
                $entityManager->remove($lignee);
                $entityManager->flush();
                $this->addFlash('success', 'La lignée a été supprimée.');

                return $this->redirectToRoute('lignee.list');
            }
        }

        return $this->render('lignee/update.twig', [
            'lignee' => $lignee,
            'form' => $form->createView(),
        ]);
    }
}
