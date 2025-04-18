<?php

namespace App\Controller;

use _PHPStan_c875e8309\Nette\Utils\DateTime;
use App\Entity\Document;
use App\Entity\LogAction;
use App\Entity\User;
use App\Enum\FolderType;
use App\Enum\Role;
use App\Form\DeleteForm;
use App\Repository\BaseRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\PagerService;
use App\Service\PersonnageService;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\Deprecated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
        protected FileUploader $fileUploader,
        protected readonly TranslatorInterface $translator,
        protected readonly SluggerInterface $slugger,
        protected PagerService $pageRequest,
        protected MailService $mailer,
        protected LoggerInterface $logger,
        protected PersonnageService $personnageService,
        // Cache $cache, // TODO : later
    ) {
    }

    protected function ListSearchForm(): FormInterface
    {
        return $this->pageRequest->getForm();
    }

    protected function genericDelete(
        $entity,
        string $title,
        string $successMsg,
        string $redirect,
        array $breadcrumb,
        string $content = '',
    ): RedirectResponse|Response {
        $request = $this->requestStack->getCurrentRequest();
        $form = $this->createForm(DeleteForm::class, $entity, ['class' => $entity::class]);
        $form->handleRequest($request);

        $entityToDelete = null;
        if ($request && 'DELETE' === $request->getMethod()) {
            $entityToDelete = $entity;
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $entityToDelete = $form->getData();
        }

        if ($entityToDelete) {
            // Soft or hard delete ?
            if (method_exists($entityToDelete, 'getDeletedAt')) {
                $entityToDelete->setDeletedAt(new \DateTime('NOW'));
                $this->entityManager->persist($entityToDelete);
            } else {
                $this->entityManager->remove($entityToDelete);
            }
            $this->entityManager->flush();

            $this->addFlash('success', $successMsg);

            try {
                return $this->redirectToRoute($redirect, [], 303);
            } catch (\Exception $e) {
                return $this->redirect($redirect, '303');
            }
        }

        return $this->render('_partials/delete.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'entity' => $entity,
            'breadcrumb' => $breadcrumb,
            'content' => $content,
        ]);
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        // TODO enhance
        $request = $this->requestStack?->getCurrentRequest();
        if ($this->isGranted('ROLE_ADMIN') && $this->container->get('twig')->getLoader()->exists('admin/'.$view)) {
            $currentParameters = $request->attributes->get('_route_params');
            $currentParameters['playerView'] = !$request->get('playerView');

            $parameters['playerViewToggleUrl'] = $this->generateUrl(
                $request->attributes->get('_route'),
                $currentParameters
            );

            if (false !== (bool) $request->get('playerView')) {
                return parent::render('admin/'.$view, $parameters, $response);
            }
        }

        return parent::render($view, $parameters, $response);
    }

    protected function getRequestLimit(int $defLimit = 10): int
    {
        return $this->pageRequest->getLimit($defLimit);
    }

    #[Deprecated]
    protected function getRequestOrder(
        string $defOrderBy = 'id',
        string $defOrderDir = 'ASC',
        ?string $alias = null,
        ?array $allowedFields = null, // TODO: check SF security Form on Self Entity's attributes
    ): array {
        $request = $this->requestStack?->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $orderBy = $request->query->getString('order_by', $defOrderBy);
        $orderDir = $this->getRequestOrderDir($defOrderDir);

        if (!empty($allowedFields) && !\in_array($orderBy, $allowedFields, true)) {
            $orderBy = $defOrderBy;
        }

        if ($alias) {
            $orderBy = $alias.'.'.$orderBy;
        }

        return [$orderBy => $orderDir];
    }

    protected function getRequestOrderDir(string $defOrderDir = 'ASC'): string
    {
        return $this->pageRequest->getOrderBy()->setDefaultOrderDir($defOrderDir)->getSort();
    }

    // TODO change to orderBy service

    protected function getRequestPage(int $defPage = 1): int
    {
        return $this->pageRequest->getPage($defPage);
    }

    protected function handleCreateOrUpdate(
        Request $request,
        $entity,
        string $formClass,
        array $breadcrumb = [],
        array $routes = [],
        array $msg = [],
        ?callable $entityCallback = null,
    ): RedirectResponse|Response {
        $repository = $this->entityManager->getRepository($entity::class);
        if (!$repository instanceof BaseRepository || !$repository->isEntity($entity)) {
            throw new \RuntimeException('Entity must be a doctrine entity and have a repository');
        }

        $form = $this->createForm($formClass, $entity);
        $isNew = !$this->entityManager->getUnitOfWork()->isInIdentityMap($entity);

        try {
            $root = $routes['root']
                ?? (new \ReflectionClass(static::class))
                ?->getAttributes(Route::class)[0]
                ?->getArguments()['name'] ?? '';
            $routes['root'] = $root; // ensure if from other
        } catch (\ErrorException $e) {
            $this->logger->error($e);
            throw new \RuntimeException(<<<'EOF'
                Unable to get the root route.
                If you do not define a main route as class attributes (ie: #[Route('/groupe', name: 'groupe.')]), 
                You may need to provide the argument $routes['root'] from the calling methods.
                Sample: ['root' => 'groupe.'] from GroupeController::handleCreateOrUpdate()
                EOF);
        }

        $routes['add'] ??= $root.'add';
        $routes['list'] ??= ($routes['index'] ?? $root.'list');
        $routes['delete'] ??= $root.'delete';
        $routes['update'] ??= $root.'update';
        $routes['detail'] ??= ($routes['view'] ?? $root.'detail');
        $routes['entityAlias'] ??= $root ? trim($root, ".\n\r\t\v\0") : $repository::getEntityAlias();

        $msg['entity'] ??= $this->translator->trans('donnée', domain: 'controller');
        $msg['entity_added'] ??= $this->translator->trans('La donnée a été ajoutée', domain: 'controller');
        $msg['entity_deleted'] ??= $this->translator->trans('La donnée a été supprimée', domain: 'controller');
        $msg['entity_updated'] ??= $this->translator->trans('La donnée a été mise à jour', domain: 'controller');
        $msg['entity_list'] ??= $this->translator->trans('Liste des données', domain: 'controller');
        $msg['save'] ??= $this->translator->trans('Sauvegarder', domain: 'controller');
        $msg['save_continue'] ??= $this->translator->trans('Sauvegarder & continuer', domain: 'controller');
        $msg['delete'] ??= $this->translator->trans('Supprimer', domain: 'controller');
        $msg['title_add'] ??= $this->translator->trans('Ajouter une donnée', domain: 'controller');
        $msg['title_update'] ??= $this->translator->trans('Modifier la donnée', domain: 'controller');
        $msg['title'] ??= $isNew ? $msg['title_add'] : $msg['title_update'];

        if (empty($breadcrumb)) {
            if (!empty($routes['list'])) {
                $breadcrumb[] = ['name' => $msg['entity_list'], 'route' => $this->generateUrl($routes['list'])];
            }
            if (!$isNew) {
                $label = method_exists($entity, 'getLabel') ? $entity->getLabel() : $msg['entity'];
                $breadcrumb[] = [
                    'name' => $label,
                    'route' => $this->generateUrl($routes['detail'], [$routes['entityAlias'] => $entity->getId()]),
                ];
            }
            $breadcrumb[] = ['name' => $msg['title']];
        }
        if ($isNew) {
            $form->add(
                'save',
                SubmitType::class,
                [
                    'label' => $msg['save'],
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ]
            )
                ->add('save_continue', SubmitType::class, [
                    'label' => $msg['save_continue'],
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ]);
        } else {
            $form->add('update', SubmitType::class, [
                'label' => $msg['save'],
                'attr' => [
                    'class' => 'btn btn-secondary',
                ],
            ])
                ->add('delete', SubmitType::class, [
                    'label' => $msg['delete'],
                    'attr' => [
                        'class' => 'btn btn-secondary',
                    ],
                ]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            if (is_callable($entityCallback)) {
                $entity = $entityCallback($entity, $form);
            }

            if ($form->has('save_continue') && $form->get('save_continue')->isClicked()) {
                $this->addFlash('success', $msg['entity_added']);
                $this->entityManager->persist($entity);
                $this->entityManager->flush();

                return $this->redirectToRoute($routes['add']);
            }

            $logType = 'entity';

            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $logType = 'entity_delete';
                $this->entityManager->remove($entity);
                $this->addFlash('success', $msg['entity_deleted']);
            } else {
                $this->entityManager->persist($entity);

                if ($form->has('update') && $form->get('update')->isClicked()) {
                    $logType = 'entity_update';
                    $this->addFlash('success', $msg['entity_updated']);
                }

                if ($form->has('save') && $form->get('save')->isClicked()) {
                    $logType = 'entity_add';
                    $this->addFlash('success', $msg['entity_added']);
                }
            }

            $this->log($entity, $logType);

            $this->entityManager->flush();

            return $form->has('update')
                ? $this->redirectToRoute($routes['list'])
                : $this->redirectToRoute($routes['detail'], [trim($routes['root'], '.') => $entity->getId()]);
        }

        return $this->render('_partials/addOrUpdateForm.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'msg' => $msg,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    protected function log(mixed $entity, string $type, bool $flush = false): void
    {
        $logAction = new LogAction();
        $logAction->setDate(new DateTime());
        $logAction->setUser($this->getUser());
        $logAction->setType($type);

        if (method_exists($entity, 'toLog')) {
            $entityValue = $entity->toLog();
        } else {
            $entityValue = (array) $entity;
            // Clean a bit
            foreach ($entityValue as $key => $value) {
                $cleanKey = str_replace([$entity::class, ' ', '*'], ['', '', ''], $key);
                if ($cleanKey !== $key) {
                    $entityValue[$cleanKey] = $value;
                    unset($entityValue[$key]);
                }
            }
        }

        $entityData = ['class' => $entity::class, 'data' => $entityValue];

        $logAction->setData($entityData);
        $this->entityManager->persist($logAction);
        $flush && $this->entityManager->flush();
    }

    protected function redirectToReferer(Request $request): ?RedirectResponse
    {
        $referer = $request->headers->get('referer');

        if ($referer) {
            return $this->redirect($referer, 303);
        }

        return null;
    }

    protected function sendCsv(
        string $title,
        ?BaseRepository $repository = null,
        array $header = [],
        ?callable $content = null,
    ): StreamedResponse {
        if (!$repository && !$content) {
            throw new \Exception('Method need a repository or a callable content');
        }

        $response = new StreamedResponse();
        $response->headers->set('Content-Control', 'private');
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$this->slugger->slug($title).'.csv');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        if (null === $content) {
            $content = static function () use ($repository, $header) {
                $output = fopen('php://output', 'wb');

                $iterateMode = $repository::ITERATE_EXPORT_HEADER;
                if ($header) {
                    $iterateMode = $repository::ITERATE_EXPORT;
                    fputcsv($output, $header, ';');
                }

                foreach ($repository->findIterable(iterableMode: $iterateMode) as $data) {
                    fputcsv($output, $data, ';');
                }
                fclose($output);
            };
        }

        $response->setCallback($content);

        return $response;
    }

    protected function sendDocument(
        mixed $entity,
        ?Document $document = null,
        bool $hasAttachement = true,
    ): BinaryFileResponse {
        // TODO check usage of entity Document

        // TODO on ne peux télécharger que les documents des compétences que l'on connait
        // TODO on ne peux télécharger que les documents auquel on a accéss
        if (null === $entity && $document instanceof Document) {
            $entity = $document;
        }

        if (method_exists($entity, 'getHasDocument')) {
            // Ensure folder type and filetype
            if (method_exists($entity, 'initFile')) {
                $entity->initFile();
            }
            // get as Document entity to ensure interface
            $entity = $entity->getHasDocument();
        }

        if ($entity && !method_exists($entity, 'getDocument')) {
            throw new \RuntimeException('Missing method getDocument for given Entity');
        }

        if ($entity && !method_exists($entity, 'getDocumentUrl')) {
            throw new \RuntimeException('Missing method getDocumentUrl for given Entity');
        }

        if ($entity && !method_exists($entity, 'getPrintLabel')) {
            throw new \RuntimeException('Missing method getPrintLabel for given Entity');
        }

        $projectDir = $this->getParameter('kernel.project_dir')
            ? $this->getParameter('kernel.project_dir').'/'
            : $this->fileUploader->getProjectDirectory();
        $entity->setProjectDir($projectDir);
        $filename = $entity->getDocument();
        $documentUrl = $entity->getDocumentUrl();
        $documentLabel = $entity->getPrintLabel();

        // TRY FROM 1 on first failed
        if (!file_exists($filename)) {
            $filename = $entity->getOldV1Document();
        }

        if (!$documentUrl || !file_exists($filename)) {
            throw new NotFoundHttpException("Le document n'existe pas");
        }

        $response = (new BinaryFileResponse($filename, Response::HTTP_OK));

        if ($hasAttachement) {
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $documentLabel.'.pdf'
            );
        } else {
            $response
                ->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_INLINE,
                    $documentLabel.'.pdf'
                );
        }

        $response->headers->set('Content-Control', 'private');
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-length', filesize($filename));

        return $response;
    }

    /*
     * Sample
     *
     * For a modal confirm button :
     * ->add('delete', ButtonType::class, [
     *     'label' => 'Supprimer',
     *     'attr' => [
     *         'value' => 'Submit',
     *         'data-bs-toggle' => 'modal',
     *         'data-bs-target' => '#mainModal',
     *         'data-bs-title' => 'Confirmation',
     *         'data-bs-body' => 'Confirmez-vous vouloir supprimer cette entrée ?',
     *         'data-bs-action' => $this->generateUrl('age.delete', ['age' => $age->getId()]),
     *         'class' => 'btn btn-secondary btn-confirm-conf',
     *     ],
     * ]
     * );
     */

    protected function sendNoImageAvailable($path = 'no'): BinaryFileResponse
    {
        $response = new BinaryFileResponse(
            $this->fileUploader->getDirectory(
                FolderType::Private
            ).'No_Image_Available.jpg'
        );
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->headers->set('Content-Control', 'private');
        if ($this->isGranted('ROLE_ADMIN')) {
            $response->headers->set('Content-X-Path', $path);
        }

        return $response->send();
    }

    protected function checkHasAccess(array $roles, ?callable $callable): void
    {

        /** @var User $loggedUser */
        $loggedUser = $this->getUser();
        // Doit être connecté
        if (!$loggedUser || !$this->isGranted(Role::USER->value)) {
            throw new AccessDeniedException();
        }

        // Est un niveau admin suffisant
        if ($roles) {
            /** @var Role $role */
            foreach ($roles as $role) {
                if ($this->isGranted($role->value)) {
                    return;
                }
            }
        }

        if (is_callable($callable) && $callable()) {
            return;
        }

        throw new AccessDeniedException();
    }
}
