<?php


namespace App\Controller;

use App\Entity\GroupeLangue;
use App\Entity\Langue;
use App\Form\LangueForm;
use App\Form\Groupe\GroupeLangueForm;
use App\Repository\LangueRepository;
use App\Service\PagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted('ROLE_SCENARISTE')]
class LangueController extends AbstractController
{
    final public const DOC_PATH = __DIR__.'/../../private/doc/';

    /**
     * affiche la liste des langues.
     */
    #[Route('/langue', name: 'langue.index')]
    public function indexAction(Request $request, EntityManagerInterface $entityManager): Response
    {
        $langues = $entityManager->getRepository(Langue::class)->findAllOrderedByLabel();
        $groupeLangues = $entityManager->getRepository(GroupeLangue::class)->findAllOrderedByLabel();

        return $this->render('langue/index.twig', ['langues' => $langues, 'groupeLangues' => $groupeLangues]);
    }

    /**
     * Detail d'une langue.
     */
    #[Route('/langue/{langue}/detail', name: 'langue.detail')]
    public function detailAction(Langue $langue): Response
    {
        return $this->render('langue/detail.twig', ['langue' => $langue]);
    }

    /**
     * Ajoute une langue.
     */
    #[Route('/langue/add', name: 'langue.add')]
    public function addAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $langue = new Langue();

        $form = $this->createForm(LangueForm::class, $langue)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isSubmitted() && $form->isValid()) {
            $langue = $form->getData();
            if (self::handleDocument($request, $entityManager, $form, $langue)) {
                $entityManager->persist($langue);
                $entityManager->flush();

               $this->addFlash('success', 'La langue a été ajoutée.');

                // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
                // vers le formulaire d'ajout d'une langue
                if ($form->get('save')->isClicked()) {
                    return $this->redirectToRoute('langue.index', [], 303);
                } elseif ($form->get('save_continue')->isClicked()) {
                    return $this->redirectToRoute('langue.add', [], 303);
                }
            }
        }

        return $this->render('langue/add.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie une langue. Si l'utilisateur clique sur "sauvegarder", la langue est sauvegardée et
     * l'utilisateur est redirigé vers la liste des langues.
     * Si l'utilisateur clique sur "supprimer", la langue est supprimée et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    #[Route('/langue/{langue}/update', name: 'langue.update')]
    public function updateAction(Request $request,  EntityManagerInterface $entityManager, Langue $langue): Response|RedirectResponse
    {
        $hasDocumentUrl = !empty($langue->getDocumentUrl());
        $canBeDeleted = $langue->getPersonnageLangues()->isEmpty()
            && $langue->getTerritoires()->isEmpty()
            && $langue->getDocuments()->isEmpty();

        $deleteTooltip = $canBeDeleted ? '' : 'Cette langue est référencée par '.$langue->getPersonnageLangues()->count().' personnages, '.$langue->getTerritoires()->count().' territoires et '.$langue->getDocuments()->count().' documents et ne peut pas être supprimée';

        $formBuilder = $this->createForm(LangueForm::class, $langue, ['hasDocumentUrl' => $hasDocumentUrl])
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer', 'disabled' => !$canBeDeleted, 'attr' => ['title' => $deleteTooltip]]);

        $form = $formBuilder;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $langue = $form->getData();

            if ($form->get('update')->isClicked()) {
                if (self::handleDocument($request, $entityManager, $form, $langue)) {
                    $entityManager->persist($langue);
                    $entityManager->flush();
                    $this->addFlash('success', 'La langue a été mise à jour.');

                    return $this->redirectToRoute('langue.detail', ['langue' => $langue->getId()], 303);
                }
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($langue);
                $entityManager->flush();
                // delete language document if it exists
                self::tryDeleteDocument($langue);
               $this->addFlash('success', 'La langue a été supprimée.');

                return $this->redirectToRoute('langue.index', [], 303);
            }
        }

        return $this->render('langue/update.twig', [
            'langue' => $langue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Gère le document uploadé et renvoie true si il est valide, false sinon.
     */
    private function handleDocument(Request $request,  EntityManagerInterface $entityManager, Form $form, Langue $langue): bool
    {
        $files = $request->files->get($form->getName());
        $documentFile = $files['document'];
        // Si un document est fourni, l'enregistrer
        if (null != $documentFile) {
            $filename = $documentFile->getClientOriginalName();
            $extension = pathinfo((string) $filename, PATHINFO_EXTENSION);

            if (!$extension || 'pdf' != $extension) {
               $this->addFlash('error', 'Désolé, votre document n\'est pas valide. Vérifiez le format de votre document ('.$extension.'), seuls les .pdf sont acceptés.');

                return false;
            }

            $documentFilename = hash('md5', $langue->getLabel().$filename.time()).'.'.$extension;

            $documentFile->move(self::DOC_PATH, $documentFilename);

            // delete previous language document if it exists
            self::tryDeleteDocument($langue);

            $langue->setDocumentUrl($documentFilename);
        }

        return true;
    }

    /**
     * Supprime le document spécifié, en cas d'erreur, ne fait rien pour le moment.
     *
     * @param Langue $langue
     */
    private function tryDeleteDocument($langue): void
    {
        try {
            if (!empty($langue->getDocumentUrl())) {
                $docFilePath = self::DOC_PATH.$langue->getDocumentUrl();
                unlink($docFilePath);
            }
        } catch (FileException) {
            // for now, simply ignore
        }
    }

    /**
     * Detail d'un groupe de langue.
     */
    #[Route('/langue/groupe/{groupeLangue}/detail', name: 'langue.detailGroup')]
    public function detailGroupAction(Request $request,  EntityManagerInterface $entityManager, GroupeLangue $groupeLangue): Response
    {
        return $this->render('langue/detailGroup.twig', ['groupeLangue' => $groupeLangue]);
    }

    /**
     * Ajoute un groupe de langue.
     */
    #[Route('/langue/groupe/add', name: 'langue.addGroup')]
    public function addGroupAction(Request $request,  EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        $groupeLangue = new GroupeLangue();

        $form = $this->createForm(GroupeLangueForm::class, $groupeLangue)
            ->add('save', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('save_continue', SubmitType::class, ['label' => 'Sauvegarder & continuer']);

        $form->handleRequest($request);

        // si l'utilisateur soumet une nouvelle langue
        if ($form->isSubmitted() && $form->isValid()) {
            $groupeLangue = $form->getData();
            $entityManager->persist($groupeLangue);
            $entityManager->flush();

           $this->addFlash('success', 'Le groupe de langue a été ajouté.');

            // l'utilisateur est redirigé soit vers la liste des langues, soit vers de nouveau
            // vers le formulaire d'ajout d'une langue
            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('langue.index', [], 303);
            } elseif ($form->get('save_continue')->isClicked()) {
                return $this->redirectToRoute('langue.addGroup', [], 303);
            }
        }

        return $this->render('langue/addGroup.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modifie un groupe de langue. Si l'utilisateur clique sur "sauvegarder", le groupe de langue est sauvegardé.
     * Si l'utilisateur clique sur "supprimer", le groupe de langue est supprimé et l'utilisateur est
     * redirigé vers la liste des langues.
     */
    #[Route('/langue/groupe/{groupeLangue}/update', name: 'langue.updateGroup')]
    public function updateGroupAction(Request $request,  EntityManagerInterface $entityManager, GroupeLangue $groupeLangue): Response|RedirectResponse
    {
        $canBeDeleted = $groupeLangue->getLangues()->isEmpty();
        $deleteTooltip = $canBeDeleted ? '' : 'Ce groupe est référencé par '.$groupeLangue->getLangues()->count().' langues et ne peut pas être supprimé';

        $formBuilder = $this->createForm(GroupeLangueForm::class, $groupeLangue)
            ->add('update', SubmitType::class, ['label' => 'Sauvegarder'])
            ->add('delete', SubmitType::class, ['label' => 'Supprimer', 'disabled' => !$canBeDeleted, 'attr' => ['title' => $deleteTooltip]]);

        $form = $formBuilder;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $groupeLangue = $form->getData();

            if ($form->get('update')->isClicked()) {
                $entityManager->persist($groupeLangue);
                $entityManager->flush();
               $this->addFlash('success', 'Le groupe de langue a été mis à jour.');

                return $this->redirectToRoute('langue.detailGroup', ['groupeLangue' => $groupeLangue->getId()], 303);
            } elseif ($form->get('delete')->isClicked()) {
                $entityManager->remove($groupeLangue);
                $entityManager->flush();
               $this->addFlash('success', 'Le groupe de langue a été supprimé.');

                return $this->redirectToRoute('langue.index', [], 303);
            }
        }

        return $this->render('langue/updateGroup.twig', [
            'groupeLangue' => $groupeLangue,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Obtenir le document lié a une langue.
     */
    #[Route('/langue/{langue}/document', name: 'langue.document')]
    public function adminDocumentAction(Request $request, Langue $langue): BinaryFileResponse
    {
        $document = $langue->getDocumentUrl();
        $file = self::DOC_PATH.$document;

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Control', 'private');
        $response->headers->set('Content-Type', 'text/pdf');
        $response->headers->set('Content-length', filesize($file));
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$langue->getPrintLabel().'.pdf"');

        return $response;
    }
}
