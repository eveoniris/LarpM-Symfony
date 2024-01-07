<?php


namespace App\Controller;

use App\Entity\Technologie;
use App\Entity\TechnologiesRessources;
use App\Form\Technologie\TechnologieDeleteForm;
use App\Form\Technologie\TechnologieForm;
use App\Form\Technologie\TechnologiesRessourcesForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_REGLE')]
class TechnologieController extends AbstractController
{
    /**
     * Liste des technologie.
     */
    #[Route('/technologie', name: 'technologie.index')]
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $technologies = $entityManager->getRepository(Technologie::class)->findAllOrderedByLabel();

        return $this->render('admin\technologie\index.twig', [
            'technologies' => $technologies,
        ]);
    }

    /**
     * Ajout d'une technologie.
     */
    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(TechnologieForm::class, new Technologie())->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $technologie = $form->getData();

            $files = $request->files->get($form->getName());
            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('technologie', [], 303);
                }

                $documentFilename = hash('md5', $technologie->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $technologie->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($technologie);
            $entityManager->flush();
           $this->addFlash('success', 'La technologie a été ajoutée.');

            return $this->redirectToRoute('technologie', [], 303);
        }

        return $this->render('admin\technologie\add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Détail d'une technologie.
     */
    public function detailAction(Request $request,  EntityManagerInterface $entityManager, Technologie $technologie)
    {
        return $this->render('admin\technologie\detail.twig', [
            'technologie' => $technologie,
        ]);
    }

    /**
     * Mise à jour d'une technologie.
     */
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Technologie $technologie)
    {
        $form = $this->createForm(TechnologieForm::class, $technologie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $technologie = $form->getData();

            $files = $request->files->get($form->getName());
            // Si un document est fourni, l'enregistrer
            if (null != $files['document']) {
                $path = __DIR__.'/../../../private/doc/';
                $filename = $files['document']->getClientOriginalName();
                $extension = 'pdf';

                if (!$extension || 'pdf' !== $extension) {
                   $this->addFlash('error', 'Désolé, votre document ne semble pas valide (vérifiez le format de votre document)');

                    return $this->redirectToRoute('technologie', [], 303);
                }

                $documentFilename = hash('md5', $technologie->getLabel().$filename.time()).'.'.$extension;

                $files['document']->move($path, $documentFilename);

                $technologie->setDocumentUrl($documentFilename);
            }

            $entityManager->persist($technologie);
            $entityManager->flush();

           $this->addFlash('success', 'La technologie a été mise à jour.');

            return $this->redirectToRoute('technologie', [], 303);
        }

        return $this->render('admin\technologie\update.twig', [
            'form' => $form->createView(),
            'technologie' => $technologie,
        ]);
    }

    /**
     * Suppression d'une technologie.
     */
    public function deleteAction(Request $request,  EntityManagerInterface $entityManager, Technologie $technologie)
    {
        $form = $this->createForm(TechnologieDeleteForm::class, $technologie)
            ->add('submit', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['label' => 'Supprimer']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $technologie = $form->getData();

            $entityManager->remove($technologie);
            $entityManager->flush();

           $this->addFlash('success', 'La technologie a été supprimée.');

            return $this->redirectToRoute('technologie', [], 303);
        }

        return $this->render('admin\technologie\delete.twig', [
            'form' => $form->createView(),
            'technologie' => $technologie,
        ]);
    }

    /**
     * Liste des personnages ayant cette technologie.
     *
     * @param Technologie
     */
    public function personnagesAction(Request $request,  EntityManagerInterface $entityManager, Technologie $technologie)
    {
        $routeName = 'technologie.personnages';
        $routeParams = ['technologie' => $technologie->getId()];
        $twigFilePath = 'admin/technologie/personnages.twig';
        $columnKeys = ['colId', 'colStatut', 'colNom', 'colClasse', 'colGroupe', 'colUser'];
        $personnages = $technologie->getPersonnages();
        $additionalViewParams = [
            'technologie' => $technologie,
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

    /**
     * Ajout d'une ressource à une technologie.
     */
    public function addRessourceAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $technologieId = $request->get('technologie');
        $technologie = $entityManager->find(Technologie::class, $technologieId);
        $technologieNom = $technologie->getLabel();

        $form = $this->createForm(new TechnologiesRessourcesForm())->getForm();

        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            $technologieRessource = $form->getData();
            $ressourceId = $technologieRessource->getRessource()->getId();
            $ressourceNom = $technologieRessource->getRessource()->getLabel();

            // recherche une instance de TechnologiesRessources correspondant à la technologie et la ressource sélectionnées
            $oldRessource = $entityManager->getRepository(TechnologiesRessources::class)
                ->findOneBy(['technologie' => $technologieId, 'ressource' => $ressourceId]);
            $newQuantite = $technologieRessource->getQuantite();

            // Si la TechnologiesRessources existe déjà
            if ($oldRessource) {
                // mise à jour de la Quantite
                $oldRessource->setQuantite($newQuantite);
            } else {
                // création d'une nouvelle entrée TechnologiesRessources
                $technologieRessource->setTechnologie($technologie);
                $entityManager->persist($technologieRessource);
            }

            $entityManager->flush();
           $this->addFlash('success', $technologieNom.' requiert désormais '.$newQuantite.' '.$ressourceNom);

            return $this->redirectToRoute('technologie', [], 303);
        }

        return $this->render('admin\technologie\addRessource.twig', [
            'technologie' => $technologieId,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Retrait d'une ressource à une technologie.
     */
    public function removeRessourceAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $technologieId = $request->get('technologie');
        $technologie = $entityManager->find(Technologie::class, $technologieId);
        $technologieNom = $technologie->getLabel();
        $ressourceNom = $request->get('ressourceNom');
        $ressource = $request->get('ressource');

        $technologieRessource = $entityManager->getRepository(TechnologiesRessources::class)
            ->findOneBy(['technologie' => $technologieId, 'ressource' => $ressource]);

        $entityManager->remove($technologieRessource);
        $entityManager->flush();

       $this->addFlash('success', $technologieNom.' ne requiert plus de '.$ressourceNom.'.');

        return $this->redirectToRoute('technologie', [], 303);
    }

    /**
     * Obtenir le document lié a une technologie.
     */
    public function getTechnologieDocumentAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $technologie = $request->get('technologie');
        $document = $technologie->getDocumentUrl();
        $file = __DIR__.'/../../../private/doc/'.$document;

        $stream = static function () use ($file): void {
            readfile($file);
        };

        return $app->stream($stream, 200, [
            'Content-Type' => 'text/pdf',
            'Content-length' => filesize($file),
            'Content-Disposition' => 'attachment; filename="'.$technologie->getPrintLabel().'.pdf"',
        ]);
    }
}
