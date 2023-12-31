<?php

namespace App\Controller;

use App\Repository\BaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
        // Cache $cache,
    ) {
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

    protected function getRequestOrder(
        string $defOrderBy = 'id',
        string $defOrderDir = 'ASC',
        string $alias = null,
        array $allowedFields = null
    ): array {
        $request = $this->requestStack?->getCurrentRequest();
        if (!$request) {
            return [];
        }

        $orderBy = $request->query->getString('order_by', $defOrderBy);
        $orderDir = $request->query->getString('order_dir', $defOrderDir);

        if (!empty($allowedFields) && !\in_array($orderBy, $allowedFields, true)) {
            $orderBy = $defOrderBy;
        }

        if (!\in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = $defOrderDir;
        }

        if ($alias) {
            $orderBy = $alias.'.'.$orderBy;
        }

        return [$orderBy => $orderDir];
    }

    protected function getPagninatorProperties(
        BaseRepository $entityRepository,
        string $defOrderBy = 'id',
        int $defLimit = 50,
        string $defOrderDir = 'ASC'
    ): array {
        $request = $this->requestStack?->getCurrentRequest();

        if (!$request) {
            return [];
        }

        return [
            ...$this->getRequestOrder($defOrderBy, $defOrderDir),
            $this->getRequestLimit($defLimit),
            $this->getRequestPage(1),
        ];
    }
}
