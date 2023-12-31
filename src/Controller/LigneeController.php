<?php

namespace App\Controller;

use App\Entity\Lignee;
use App\Entity\PersonnageLignee;
use JasonGrimes\Paginator;
use LarpManager\Form\Lignee\LigneeAddMembreForm;
use LarpManager\Form\Lignee\LigneeFindForm;
use LarpManager\Form\Lignee\LigneeForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\LigneeController.
 *
 * @author gerald
 */
class LigneeController extends AbstractController
{
    /**
     * Liste des lignées.
     */
    public function listAction(Request $request, Application $app)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $type = null;
        $value = null;

        $form = $app['form.factory']->createBuilder(new LigneeFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $data = $form->getData();
            $type = $data['type'];
            $value = $data['search'];
        }

        $repo = $app['orm.em']->getRepository(Lignee::class);

        $lignees = $repo->findList(
            $type,
            $value,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($type, $value);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('lignee.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $app['twig']->render('admin/lignee/list.twig', [
            'form' => $form->createView(),
            'lignees' => $lignees,
            'paginator' => $paginator,
        ]);
    }

    /**
     * Affiche le détail d'une lignée.
     */
    public function detailAction(Request $request, Application $app)
    {
        $id = $request->get('lignee');

        $lignee = $app['orm.em']->find(Lignee::class, $id);

        /*
         * Si la lignee existe, on affiche ses détails
         * Sinon on envoie une erreur
         */
        if ($lignee) {
            return $app['twig']->render('admin/lignee/details.twig', ['lignee' => $lignee]);
        } else {
           $this->addFlash('error', 'La lignee n\'a pas été trouvée.');

            return $this->redirectToRoute('lignee');
        }
    }

    /**
     * Ajout d'une lignée.
     */
    public function addAction(Request $request, Application $app)
    {
        $lignee = new Lignee();

        $form = $app['form.factory']->createBuilder(new LigneeForm(), $lignee)
            ->add('save', 'submit', ['label' => 'Sauvegarder et retour à la liste'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder et nouvelle lignée'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $lignee = $form->getData();

            $app['orm.em']->persist($lignee);
            $app['orm.em']->flush();
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

        return $app['twig']->render('admin/lignee/add.twig', ['form' => $form->createView()]);
    }

    /**
     * Modification d'une lignée.
     */
    public function updateAction(Request $request, Application $app)
    {
        $id = $request->get('lignee');

        $lignee = $app['orm.em']->find(Lignee::class, $id);

        $form = $app['form.factory']->createBuilder(new LigneeForm(), $lignee)
            ->add('update', 'submit', ['label' => 'Sauvegarder'])
            ->add('delete', 'submit', [
                'label' => 'Supprimer',
                'attr' => ['onclick' => 'return confirm("Vous vous apprêtez à supprimer cette lignée. Confirmer ?")']])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $lignee = $form->getData();

            /*
             * Si l'utilisateur a cliqué sur "update", on met à jour la lignée
             * Si l'utilisateur a cliqué sur "delete", on supprime la lignée
             */
            if ($form->get('update')->isClicked()) {
                $app['orm.em']->persist($lignee);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La lignée a été mise à jour.');

                return $this->redirectToRoute('lignee.details', ['lignee' => $id]);
            } elseif ($form->get('delete')->isClicked()) {
                // supprime le lien entre les personnages et le groupe
                foreach ($lignee->getPersonnageLignees() as $personnage) {
                    $app['orm.em']->remove($personnage);
                }
                $app['orm.em']->remove($lignee);
                $app['orm.em']->flush();
               $this->addFlash('success', 'La lignée a été supprimée.');

                return $this->redirectToRoute('lignee.list');
            }
        }

        return $app['twig']->render('admin/lignee/update.twig', [
            'lignee' => $lignee,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajoute un nouveau membre à la lignée.
     */
    public function addMembreAction(Request $request, Application $app)
    {
        $id = $request->get('lignee');
        $lignee = $app['orm.em']->find(Lignee::class, $id);

        $form = $app['form.factory']->createBuilder(new LigneeAddMembreForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $personnage = $form['personnage']->getData();
            $parent1 = $form['parent1']->getData();
            $parent2 = $form['parent2']->getData();

            $membre = new PersonnageLignee();
            $membre->setPersonnage($personnage);
            $membre->setParent1($parent1);
            $membre->setParent2($parent2);
            $membre->setLignee($lignee);

            $app['orm.em']->persist($membre);
            $app['orm.em']->flush();

           $this->addFlash('success', 'le personnage a été ajouté à la lignée.');

            return $this->redirectToRoute('lignee.details', ['lignee' => $lignee->getId()], 303);
        }

        return $app['twig']->render('admin/lignee/addMembre.twig', [
            'lignee' => $lignee,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retire un membre à la lignée.
     */
    public function removeMembreAction(Request $request, Application $app)
    {
        $lignee = $request->get('lignee');
        $membreNom = $request->get('membreNom');
        $membre = $request->get('membre');

        $personnageLignee = $app['orm.em']->find(PersonnageLignee::class, $membre);

        $app['orm.em']->remove($personnageLignee);
        $app['orm.em']->flush();

       $this->addFlash('success', $membreNom.' a été retiré de la lignée.');

        return $this->redirectToRoute('lignee.details', ['lignee' => $lignee, 303]);
    }
}
