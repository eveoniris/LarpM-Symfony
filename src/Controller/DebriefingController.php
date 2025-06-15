<?php

namespace App\Controller;

use App\Entity\Debriefing;
use App\Entity\Groupe;
use App\Enum\Role;
use App\Form\Debriefing\DebriefingDeleteForm;
use App\Form\Debriefing\DebriefingForm;
use App\Repository\DebriefingRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class DebriefingController extends AbstractController
{
    final public const DOC_PATH = __DIR__.'/../../private/doc/';

    /**
     * Ajout d'un debriefing.
     */
    #[Route('/debriefing/add', name: 'debriefing.add')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function addAction(
        Request $request,
        EntityManagerInterface $entityManager,
    ): \Symfony\Component\HttpFoundation\RedirectResponse|Response {
        $debriefing = new Debriefing();
        $groupeId = $request->get('groupe');
        if ($groupeId) {
            $groupeRepository = $entityManager->getRepository(Groupe::class);
            $groupe = $groupeRepository->find($groupeId);
            if ($groupe) {
                $debriefing->setGroupe($groupe);
            }
        }

        $form = $this->createForm(DebriefingForm::class, $debriefing, ['groupeId' => $groupeId])
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Seuls les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $debriefing = $form->getData();
            $debriefing->setUser($this->getUser());

            if ($this->handleDocument($request, $entityManager, $form, $debriefing)) {
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
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        Form $form,
        Debriefing $debriefing,
    ): bool {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null !== $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

            if ('pdf' !== $extension) {
                $this->addFlash(
                    'error',
                    'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document ('.$extension.'), seuls les .pdf sont acceptés.',
                );

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
     * Suppression d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}/delete', name: 'debriefing.delete')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function deleteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Debriefing $debriefing,
    ): \Symfony\Component\HttpFoundation\RedirectResponse|Response {
        $form = $this->createForm(DebriefingDeleteForm::class, $debriefing)
            ->add('save', SubmitType::class, ['label' => 'Supprimer']);

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
     * Détail d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}', name: 'debriefing.detail')]
    public function detailAction(
        Debriefing $debriefing,
    ): Response {
        $this->checkHasAccess([Role::SCENARISTE],
            function () use ($debriefing) {
                return $this->groupeService->isUserIsGroupeResponsable($debriefing->getGroupe())
                    || $this->groupeService->isUserIsGroupeMember($debriefing->getGroupe());
            },
        );

        return $this->render('debriefing/detail.twig', [
            'debriefing' => $debriefing,
        ]);
    }

    protected function checkHasAccess(array $roles, ?callable $callable): void
    {
        parent::checkHasAccess($roles, $callable);
    }

    /**
     * Afficher le document lié a un debriefing.
     */
    #[Route('/debriefing/{debriefing}/document', name: 'debriefing.document')]
    public function documentAction(Debriefing $debriefing): BinaryFileResponse
    {
        $this->checkHasAccess([Role::SCENARISTE],
            function () use ($debriefing) {
                return $this->groupeService->isUserIsGroupeResponsable($debriefing->getGroupe())
                    || $this->groupeService->isUserIsGroupeMember($debriefing->getGroupe());
            },
        );

        $document = $debriefing->getDocumentUrl();
        $file = self::DOC_PATH.$document;

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'text/pdf');
        $response->headers->set('Content-length', filesize($file));
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$debriefing->getPrintTitre().'.pdf"');

        return $response;
    }

    /**
     * Présentation des debriefings.
     */
    #[Route('/debriefing', name: 'debriefing.list')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function listAction(
        Request $request,
        PagerService $pagerService,
        DebriefingRepository $debriefingRepository,
    ): Response {
        $pagerService->setRequest($request)->setRepository($debriefingRepository)->setLimit(25);

        return $this->render('debriefing/list.twig', [
            'pagerService' => $pagerService,
            'paginator' => $debriefingRepository->searchPaginated($pagerService),
        ]);
    }

    /**
     * Mise à jour d'un debriefing.
     */
    #[Route('/debriefing/{debriefing}/update', name: 'debriefing.update')]
    #[IsGranted('ROLE_SCENARISTE')]
    public function updateAction(
        Request $request,
        EntityManagerInterface $entityManager,
        Debriefing $debriefing,
    ): \Symfony\Component\HttpFoundation\RedirectResponse|Response {
        $form = $this->createForm(DebriefingForm::class, $debriefing)
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'Visibilité',
                'choices' => [
                    'Seuls les scénaristes peuvent voir ceci' => 'PRIVATE',
                    'Tous les joueurs peuvent voir ceci' => 'PUBLIC',
                    'Seuls les membres du groupe peuvent voir ceci' => 'GROUPE_MEMBER',
                    'Seul le chef de groupe peut voir ceci' => 'GROUPE_OWNER',
                    'Seul l\'auteur peut voir ceci' => 'AUTHOR',
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $debriefing = $form->getData();

            if ($this->handleDocument($request, $entityManager, $form, $debriefing)) {
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
}
