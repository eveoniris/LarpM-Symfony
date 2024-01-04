<?php

namespace App\Controller;

use App\Entity\Objet;
use JasonGrimes\Paginator;
use LarpManager\Form\ObjetFindForm;
use LarpManager\Form\Stock\ObjetDeleteForm;
use LarpManager\Form\Stock\ObjetForm;
use LarpManager\Form\Stock\ObjetTagForm;
use LarpManager\Repository\ObjetRepository;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_STOCK')]
class StockObjetController extends AbstractController
{
    /**
     * Affiche la liste des objets.
     */
    #[Route('/stock/objet', name: 'stockObjet.index')]
    public function indexAction(Request $request, Application $app)
    {
        $repoRangement = $app['orm.em']->getRepository('\\'.\App\Entity\Rangement::class);
        $rangements = $repoRangement->findAll();

        $repoTag = $app['orm.em']->getRepository('\\'.\App\Entity\Tag::class);
        $tags = $repoTag->findAll();

        $repoObjet = $app['orm.em']->getRepository('\\'.\App\Entity\Objet::class);

        $objetsWithoutTagCount = $repoObjet->findCount(['tag' => ObjetRepository::CRIT_WITHOUT]);
        $objetsWithoutRangementCount = $repoObjet->findCount(['rangement' => ObjetRepository::CRIT_WITHOUT]);

        $criteria = [];

        // tag: null => no search based on tag;
        // tag: -1 or ObjetRepository::CRIT_WITHOUT => search object without
        // tag: [a-Z]+ => search object with this tag name
        $criteria['tag'] = $request->get('tag');

        // rangement: null => no search based on rangement;
        // rangement: -1 or ObjetRepository::CRIT_WITHOUT => search object without
        // rangement: [a-Z]+ => search object with this rangement name
        $criteria['rangement'] = $request->get('rangement');

        $order_by = $request->get('order_by', 'nom');
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) $request->get('limit', 50);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;

        $form = $app['form.factory']->createBuilder(new ObjetFindForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $criteria[$data['type']] = $data['value'];
        }

        $objets = $repoObjet->findList(
            $criteria,
            ['by' => $order_by, 'dir' => $order_dir],
            $limit,
            $offset
        );

        $url = $app['url_generator']->generate('stock_objet_index');

        $paginator = new Paginator(
            $repoObjet->findCount($criteria),
            $limit,
            $page,
            $url.'?page=(:num)&'.http_build_query(
                [
                    'limit' => $limit,
                    'order_by' => $order_by,
                    'order_dir' => $order_dir,
                    'tag' => $criteria['tag'],
                    'rangement' => $criteria['rangement'],
                ]
            )
        );

        return $app['twig']->render('admin/stock/objet/list.twig', [
            'objets' => $objets,
            'tag' => $criteria['tag'],
            'tags' => $tags,
            'form' => $form->createView(),
            'objetsWithoutTagCount' => $objetsWithoutTagCount,
            'objetsWithoutRangementCount' => $objetsWithoutRangementCount,
            'paginator' => $paginator,
            'rangements' => $rangements,
            'rangement' => $criteria['rangement'],
        ]);
    }

    /**
     * Fourni la liste des objets sans proprietaire.
     */
    public function listWithoutProprioAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.proprietaire IS NULL');

        $objet_without_proprio = $qb->getQuery()->getResult();

        return $app['twig']->render('admin/stock/objet/listWithoutProprio.twig', [
            'objets' => $objet_without_proprio,
        ]);
    }

    /**
     * Fourni la liste des objets sans responsable.
     */
    public function listWithoutResponsableAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.User IS NULL');

        $objet_without_responsable = $qb->getQuery()->getResult();

        return $app['twig']->render('admin/stock/objet/listWithoutResponsable.twig', [
            'objets' => $objet_without_responsable,
        ]);
    }

    /**
     * Fourni la liste des objets sans rangement.
     */
    public function listWithoutRangementAction(Request $request, Application $app)
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Objet::class);

        $qb = $repo->createQueryBuilder('o');
        $qb->select('o');
        $qb->where('o.rangement IS NULL');

        $objet_without_rangement = $qb->getQuery()->getResult();

        return $app['twig']->render('admin/stock/objet/listWithoutRangement.twig', [
            'objets' => $objet_without_rangement,
        ]);
    }

    /**
     * Affiche la détail d'un objet.
     */
    public function detailAction(Request $request, Application $app, Objet $objet)
    {
        return $app['twig']->render('admin/stock/objet/detail.twig', ['objet' => $objet]);
    }

    /**
     * Fourni les données de la photo lié à l'objet.
     */
    public function photoAction(Request $request, Application $app, Objet $objet)
    {
        $miniature = $request->get('miniature');
        $photo = $objet->getPhoto();

        if (!$photo) {
            return null;
        }

        $file = $photo->getFilename();
        $filename = __DIR__.'/../../../private/stock/'.$file;

        if ($miniature) {
            $image = $app['imagine']->open($filename);

            $stream = static function () use ($image): void {
                $size = new \Imagine\Image\Box(200, 200);
                $thumbnail = $image->thumbnail($size);
                ob_start(null, 0, PHP_OUTPUT_HANDLER_FLUSHABLE | PHP_OUTPUT_HANDLER_REMOVABLE);
                echo $thumbnail->get('jpeg');
                ob_end_flush();
            };
        } else {
            $stream = static function () use ($filename): void {
                readfile($filename);
            };
        }

        return $app->stream($stream, 200, [
            'Content-Type' => 'image/jpeg',
            'cache-control' => 'private',
        ]);
    }

    /**
     * Ajoute un objet.
     */
    public function addAction(Request $request, Application $app)
    {
        $objet = new \App\Entity\Objet();

        $objet->setNombre(1);

        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Etat::class);
        $etat = $repo->find(1);
        if ($etat) {
            $objet->setEtat($etat);
        }

        $form = $app['form.factory']->createBuilder(new ObjetForm(), $objet)
            ->add('save', 'submit', ['label' => 'Sauvegarder et fermer'])
            ->add('save_continue', 'submit', ['label' => 'Sauvegarder et nouveau'])
            ->add('save_clone', 'submit', ['label' => 'Sauvegarder et cloner'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $objet = $form->getData();

            if ($objet->getObjetCarac()) {
                $app['orm.em']->persist($objet->getObjetCarac());
            }

            if ($objet->getPhoto()) {
                $objet->getPhoto()->upload($app);
                $app['orm.em']->persist($objet->getPhoto());
            }

            /**$repo = $app['orm.em']->getRepository('\App\Entity\User');
             * $User = $repo->find(1);
             * $User->addObjetRelatedByCreateurId($objet);
             * $objet->setUserRelatedByCreateurId($User);**/

            $app['orm.em']->persist($objet);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet a été ajouté dans le stock');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('stock_homepage', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('stock_objet_add', [], 303);
            } elseif ($form->get('save_clone')->isClicked()) {
                return $this->redirectToRoute('stock_objet_clone', ['objet' => $objet->getId()], [], 303);
            }
        }

        return $app['twig']->render('admin/stock/objet/add.twig', ['form' => $form->createView()]);
    }

    /**
     * Créé un objet à partir d'un autre.
     */
    public function cloneAction(Request $request, Application $app, Objet $objet)
    {
        $newObjet = clone $objet;

        $numero = $objet->getNumero();
        if ('' !== $numero && '0' !== $numero) {
            $newObjet->setNumero($numero + 1);
        }

        $form = $app['form.factory']->createBuilder(new ObjetForm(), $newObjet)
            ->add('save', 'submit', ['label' => 'Sauvegarder et fermer'])
            ->add('save_clone', 'submit', ['label' => 'Sauvegarder et cloner'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $objet = $form->getData();

            if ($objet->getObjetCarac()) {
                $app['orm.em']->persist($objet->getObjetCarac());
            }

            if ($objet->getPhoto()) {
                $objet->getPhoto()->upload($app);
                $app['orm.em']->persist($objet->getPhoto());
            }

            $app['orm.em']->persist($objet);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet a été ajouté dans le stock');

            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('stock_homepage', [], 303);
            } else {
                return $this->redirectToRoute('stock_objet_clone', ['objet' => $newObjet->getId()], [], 303);
            }
        }

        return $app['twig']->render('admin/stock/objet/clone.twig', ['objet' => $newObjet, 'form' => $form->createView()]);
    }

    /**
     * Mise à jour un objet.
     */
    public function updateAction(Request $request, Application $app, Objet $objet)
    {
        $form = $app['form.factory']->createBuilder(new ObjetForm(), $objet)
            ->add('update', 'submit', ['label' => 'Sauvegarder et fermer'])
            ->add('delete', 'submit', ['label' => 'Supprimer'])
            ->getForm();
        $form = $form;
        $form->handleRequest($request);

        if ($form->isValid()) {
            $objet = $form->getData();

            if ($form->get('update')->isClicked()) {
                if ($objet->getObjetCarac()) {
                    $app['orm.em']->persist($objet->getObjetCarac());
                }
                if ($objet->getPhoto()) {
                    $objet->getPhoto()->upload($app);
                    $app['orm.em']->persist($objet->getPhoto());
                }
                $app['orm.em']->persist($objet);
                $app['orm.em']->flush();
               $this->addFlash('success', 'L\'objet a été mis à jour');
            } elseif ($form->get('delete')->isClicked()) {
                $app['orm.em']->remove($objet);
                $app['orm.em']->flush();
               $this->addFlash('success', 'L\'objet a été supprimé');
            }

            return $this->redirectToRoute('stock_homepage');
        }

        return $app['twig']->render('admin/stock/objet/update.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }

    /**
     * Suppression d'un objet.
     */
    public function deleteAction(Request $request, Application $app, Objet $objet)
    {
        $form = $app['form.factory']->createBuilder(new ObjetDeleteForm(), $objet)->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $objet = $form->getData();

            $app['orm.em']->remove($objet);
            $app['orm.em']->flush();

           $this->addFlash('success', 'L\'objet a été supprimé');

            return $this->redirectToRoute('stock_objet_index');
        }

        return $app['twig']->render('admin/stock/objet/delete.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }

    /**
     * Modification des tags d'un objet.
     */
    public function tagAction(Request $request, Application $app, Objet $objet)
    {
        $form = $app['form.factory']->createBuilder(new ObjetTagForm(), $objet)->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $objet = $form->getData();

            $newTags = $form['news']->getData();
            foreach ($newTags as $tag) {
                $objet->addTag($tag);
                $app['orm.em']->persist($tag);
            }

            $app['orm.em']->persist($objet);
            $app['orm.em']->flush();

           $this->addFlash('success', 'les tags ont été mis à jour');

            return $this->redirectToRoute('stock_objet_index');
        }

        return $app['twig']->render('admin/stock/objet/tag.twig', ['objet' => $objet, 'form' => $form->createView()]);
    }

    /**
     * Exporte la liste des objets au format CSV.
     */
    #[Route('/stock/objet/export', name: 'stockObjet.export')]
    public function exportAction(Request $request, Application $app): void
    {
        $repo = $app['orm.em']->getRepository('\\'.\App\Entity\Objet::class);
        $objets = $repo->findAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=eveoniris_stock_'.date('Ymd').'.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // header
        fputcsv($output,
            [
                'nom',
                'code',
                'description',
                'photo',
                'rangement',
                'etat',
                'proprietaire',
                'responsable',
                'nombre',
                'creation_date'], ',');

        foreach ($objets as $objet) {
            fputcsv($output, $objet->getExportValue(), ',');
        }

        fclose($output);
        exit;
    }
}
