<?php


namespace App\Controller;

use App\Entity\Connaissance;
use App\Form\ConnaissanceDeleteForm;
use App\Form\ConnaissanceForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class ConnaissanceController extends AbstractController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    /**
     * Liste des connaissances.
     */
    #[Route('/connaissance', name: 'connaissance.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $connaissances = $entityManager->getRepository('\\'.\App\Entity\Connaissance::class)->findAllOrderedByLabel();

        return $this->render('admin/connaissance/list.twig', [
            'connaissances' => $connaissances,
        ]);
    }

    /**
     * Detail d'une connaissance.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $connaissance = $request->get('connaissance');

        return $this->render('admin/connaissance/detail.twig', [
            'connaissance' => $connaissance,
        ]);
    }

    /**
     * Ajoute une connaissance.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $connaissance = new Connaissance();

        $form = $this->createForm(ConnaissanceForm::class(), $connaissance)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();
            $connaissance->setNiveau(1);

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('connaissance.list', [], 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($connaissance);
            $entityManager->flush();

           $this->addFlash('success', 'La connaissance a été ajoutée');

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], [], 303);
        }

        return $this->render('admin/connaissance/add.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une connaissance.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $connaissance = $request->get('connaissance');

        $form = $this->createForm(ConnaissanceForm::class(), $connaissance)
            ->add('save', 'submit', ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();

            $files = $request->files->get($form->getName());

            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('connaissance.list', [], 303);
                }

                $documentFilename = hash('md5', $connaissance->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $connaissance->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($connaissance);
            $entityManager->flush();

           $this->addFlash('success', 'La connaissance a été sauvegardée');

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], [], 303);
        }

        return $this->render('admin/connaissance/update.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une connaissance.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $connaissance = $request->get('connaissance');

        $form = $this->createForm(ConnaissanceDeleteForm::class(), $connaissance)
            ->add('save', 'submit', ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();

            $entityManager->remove($connaissance);
            $entityManager->flush();

           $this->addFlash('success', 'La connaissance a été supprimée');

            return $this->redirectToRoute('connaissance.list', [], 303);
        }

        return $this->render('admin/connaissance/delete.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une connaissance.
     */
    public function getDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $document = $request->get('document');
        $connaissance = $request->get('connaissance');

        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$connaissance->getPrintLabel().'.pdf"',
        ]);
    }

    /**
     * Liste des personnages ayant cette connaissance.
     *
     * @param Connaissance
     */
    public function personnagesAction(Request $request,  EntityManagerInterface $entityManager, Connaissance $connaissance)
    {
        $routeName = 'connaissance.personnages';
        $routeParams = ['connaissance' => $connaissance->getId()];
        $twigFilePath = 'admin/connaissance/personnages.twig';
        $columnKeys = $this->defaultPersonnageListColumnKeys;
        $personnages = $connaissance->getPersonnages();
        $additionalViewParams = [
            'connaissance' => $connaissance,
        ];

        // handle the request and return an array containing the parameters for the view
        $personnageSearchHandler = $app['personnage.manager']->getSearchHandler();

        $viewParams = $personnageSearchHandler->getSearchViewParameters(
            $request,
            $routeName,
            $routeParams,
            $columnKeys,
            $additionalViewParams,
            $personnages
        );

        return $this->render(
            $twigFilePath,
            $viewParams
        );
    }
}
