<?php


namespace App\Controller;

use App\Entity\Debriefing;
use App\Entity\Groupe;
use JasonGrimes\Paginator;
use App\Form\Debriefing\DebriefingDeleteForm;
use App\Form\Debriefing\DebriefingFindForm;
use App\Form\Debriefing\DebriefingForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class DebriefingController extends AbstractController
{
    final public const DOC_PATH = __DIR__.'/../../../private/doc/';

    /**
     * Présentation des debriefings.
     */
    #[Route('/debriefing', name: 'debriefing.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $order_by = $request->get('order_by') ?: 'id';
        $order_dir = 'DESC' === $request->get('order_dir') ? 'DESC' : 'ASC';
        $limit = (int) ($request->get('limit') ?: 50);
        $page = (int) ($request->get('page') ?: 1);
        $offset = ($page - 1) * $limit;
        $criteria = [];

        $form = $this->createForm(DebriefingFindForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // TODO
            /*$data = $form->getData();
            $type = $data['type'];
            $value = $data['value'];
            switch ($type){
                case 'Auteur':
                    $criteria[] = "g.nom LIKE '%$value%'";
                    break;
                case 'Groupe':
                    $criteria[] = "u.name LIKE '%$value%'";
                    break;
            }*/
        }

        $repo = $entityManager->getRepository('\\'.\App\Entity\Debriefing::class);
        $debriefings = $repo->findBy(
            $criteria,
            [$order_by => $order_dir],
            $limit,
            $offset);

        $numResults = $repo->findCount($criteria);

        $paginator = new Paginator($numResults, $limit, $page,
            $app['url_generator']->generate('debriefing.list').'?page=(:num)&limit='.$limit.'&order_by='.$order_by.'&order_dir='.$order_dir
        );

        return $this->render('debriefing/list.twig', [
            'debriefings' => $debriefings,
            'paginator' => $paginator,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ajout d'un debriefing.
     */
    #[Route('/debriefing/add/{groupe}', name: 'debriefing.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager, #[MapEntity] Groupe $groupe)
    {
        $debriefing = new Debriefing();
        $debriefing->setGroupe($groupe);

        $form = $this->createForm(DebriefingForm::class, $debriefing)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $debriefing = $form->getData();
            $debriefing->setUser($this->getUser());

            if ($this->handleDocument($request, $app, $form, $debriefing)) {
                $entityManager->persist($debriefing);
                $entityManager->flush();

               $this->addFlash('success', 'Le debriefing a été ajouté.');
            }

            return $this->redirectToRoute('groupe.detail', ['groupe' => $debriefing->getGroupe()->getId()], 303);
        }

        return $this->render('debriefing/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}/delete', name: 'debriefing.delete')]
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Debriefing $debriefing)
    {
        $form = $this->createForm(DebriefingDeleteForm::class, $debriefing)
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $debriefing = $form->getData();
            $entityManager->remove($debriefing);
            $entityManager->flush();

           $this->addFlash('success', 'Le debriefing a été supprimé.');

            return $this->redirectToRoute('groupe.detail', ['groupe' => $debriefing->getGroupe()->getId()], 303);
        }

        return $this->render('debriefing/delete.twig', [
            'form' => $form->createView(),
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Mise à jour d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}/update', name: 'debriefing.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Debriefing $debriefing)
    {
        $form = $this->createForm(DebriefingForm::class, $debriefing)
            ->add('visibility', 'choice', [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => $app['larp.manager']->getVisibility(),
            ])
            ->add('save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $debriefing = $form->getData();

            if ($this->handleDocument($request, $app, $form, $debriefing)) {
                $entityManager->persist($debriefing);
                $entityManager->flush();

               $this->addFlash('success', 'Le debriefing a été modifié.');

                return $this->redirectToRoute('groupe.detail', ['groupe' => $debriefing->getGroupe()->getId()], 303);
            }
        }

        return $this->render('debriefing/update.twig', [
            'form' => $form->createView(),
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Détail d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}', name: 'debriefing.detail')]
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Debriefing $debriefing)
    {
        return $this->render('debriefing/detail.twig', [
            'debriefing' => $debriefing,
        ]);
    }

    /**
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(Request $request,  EntityManagerInterface $entityManager, Form $form, Debriefing $debriefing): bool
    {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null !== $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

            if ('pdf' !== $extension) {
               $this->addFlash('error', 'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document ('.$extension.'), seuls les .pdf sont acceptés.');

                return false;
            }

            $documentFilename = hash('md5', $debriefing->getTitre().$filename.time()).'.'.$extension;

            $documentFile->move(self::DOC_PATH, $documentFilename);

            // delete previous language document if it exists
            $this->tryDeleteDocument($debriefing);

            $debriefing->setDocumentUrl($documentFilename);
        }

        return true;
    }

    /**
     * Supprime le document spécifié, en cas d'erreur, ne fait rien pour le moment.
     */
    private function tryDeleteDocument(Debriefing $debriefing): void
    {
        try {
            if (!empty($debriefing->getDocumentUrl())) {
                $docFilePath = self::DOC_PATH.$debriefing->getDocumentUrl();
                unlink($docFilePath);
            }
        } catch (FileException) {
            // for now, simply ignore
        }
    }

    /**
     * Afficher le document lié a un debriefing.
     */
    #[Route('/debriefing/{debriefing}/document', name: 'debriefing.document')]
    public function documentAction(Request $request,  EntityManagerInterface $entityManager, Debriefing $debriefing)
    {
        $document = $debriefing->getDocumentUrl();
        $file = self::DOC_PATH.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$debriefing->getPrintTitre().'.pdf"',
        ]);
    }
}
