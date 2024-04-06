<?php

namespace App\Controller;

use App\Entity\Connaissance;
use App\Enum\FolderType;
use App\Form\DeleteForm;
use App\Repository\BaseRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
        protected FileUploader $fileUploader,
        // Cache $cache, // TODO : later
    ) {
    }

    protected function sendNoImageAvailable(): BinaryFileResponse
    {
        $response = new BinaryFileResponse(
            $this->fileUploader->getDirectory(
                FolderType::Private
            ).'No_Image_Available.jpg'
        );
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->headers->set('Content-Control', 'private');

        return $response->send();
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        // dump($this->container->get('twig')->getLoader()->exists('admin/' . $view));
        if ($this->isGranted('ROLE_ADMIN') && $this->container->get('twig')->getLoader()->exists('admin/'.$view)) {
            return parent::render('admin/'.$view, $parameters, $response);
        }

        return parent::render($view, $parameters, $response);
    }

    protected function getRequestLimit(int $defLimit = 10): int
    {
        $request = $this->requestStack?->getCurrentRequest();

        if (!$request) {
            return $defLimit;
        }

        $limit = $request->query->getInt('limit', $defLimit);

        if (!is_numeric($limit)) {
            return $defLimit;
        }

        // We limit between 1 and 100
        return min(100, max(1, $limit));
    }

    protected function getRequestPage(int $defPage = 1): int
    {
        $request = $this->requestStack?->getCurrentRequest();

        if (!$request) {
            return $defPage;
        }

        $page = $request->query->getInt('page', $defPage);

        if (!is_numeric($page)) {
            $page = $defPage;
        }

        // Page can be lower than 1
        return max(1, $page);
    }

    protected function getRequestOrderDir(string $defOrderDir = 'ASC'): string
    {
        $request = $this->requestStack?->getCurrentRequest();
        if (!$request) {
            return $defOrderDir;
        }

        $orderDir = $request->query->getString('order_dir', $defOrderDir);

        if (!\in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = $defOrderDir;
        }

        return $orderDir;
    }

    protected function getRequestOrder(
        string $defOrderBy = 'id',
        string $defOrderDir = 'ASC',
        string $alias = null,
        array $allowedFields = null // TODO: check SF security Form on Self Entity's attributes
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

    protected function genericDelete($entity, string $title, string $successMsg, string $redirect, array $breadcrumb): RedirectResponse|Response
    {
        $form = $this->createForm(DeleteForm::class, $entity, ['class' => $entity::class]);

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->entityManager->remove($data);
            $this->entityManager->flush();

            $this->addFlash('success', $successMsg);

            return $this->redirectToRoute($redirect, [], 303);
        }

        return $this->render('_partials/delete.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'entity' => $entity,
            'breadcrumb' => $breadcrumb,
        ]);
    }
}
