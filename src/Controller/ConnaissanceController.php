<?php

namespace App\Controller;

use App\Entity\Connaissance;
use App\Form\ConnaissanceDeleteForm;
use App\Form\ConnaissanceForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_REGLE')]
class ConnaissanceController extends AbstractController
{
    // liste des colonnes à afficher par défaut sur les vues 'personnages' (l'ordre est pris en compte)
    private array $defaultPersonnageListColumnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];

    /**
     * Liste des connaissances.
     */
    #[Route('/connaissance', name: 'connaissance.list')]
    public function listAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $connaissances = $entityManager->getRepository(Connaissance::class)->findAllOrderedByLabel();

        return $this->render('connaissance/list.twig', [
            'connaissances' => $connaissances,
        ]);
    }

    /**
     * Detail d'une connaissance.
     */
    #[Route('/connaissance/{connaissance}', name: 'connaissance.detail')]
    public function detailAction(Request $request, #[MapEntity] Connaissance $connaissance): Response
    {
        return $this->render('connaissance/detail.twig', [
            'connaissance' => $connaissance,
        ]);
    }

    /**
     * Ajoute une connaissance.
     */
    #[Route('/connaissance/add', name: 'connaissance.add')]
    public function addAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $connaissance = new Connaissance();

        $form = $this->createForm(ConnaissanceForm::class, $connaissance)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

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

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], 303);
        }

        return $this->render('connaissance/add.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Met à jour une connaissance.
     */
    #[Route('/connaissance/{connaissance}/update', name: 'connaissance.update')]
    public function updateAction(Request $request, #[MapEntity] Connaissance $connaissance): RedirectResponse|Response
    {
        $form = $this->createForm(ConnaissanceForm::class, $connaissance)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

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

            $this->entityManager->persist($connaissance);
            $this->entityManager->flush();

            $this->addFlash('success', 'La connaissance a été sauvegardée');

            return $this->redirectToRoute('connaissance.detail', ['connaissance' => $connaissance->getId()], 303);
        }

        return $this->render('connaissance/update.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Supprime une connaissance.
     */
    #[Route('/connaissance/{connaissance}/delete', name: 'connaissance.delete')]
    public function deleteAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Connaissance $connaissance): RedirectResponse|Response
    {
        $form = $this->createForm(ConnaissanceDeleteForm::class, $connaissance)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $connaissance = $form->getData();

            $entityManager->remove($connaissance);
            $entityManager->flush();

            $this->addFlash('success', 'La connaissance a été supprimée');

            return $this->redirectToRoute('connaissance.list', [], 303);
        }

        return $this->render('connaissance/delete.twig', [
            'connaissance' => $connaissance,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une connaissance.
     */
    #[Route('/connaissance/{connaissance}/document', name: 'connaissance.document')]
    public function getDocumentAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Connaissance $connaissance)
    {
        $document = $request->get('document');

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
    #[Route('/connaissance/{connaissance}/personnages', name: 'connaissance.personnages')]
    public function personnagesAction(Request $request, EntityManagerInterface $entityManager, #[MapEntity] Connaissance $connaissance): Response
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
