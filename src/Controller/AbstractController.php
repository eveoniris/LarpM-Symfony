<?php

namespace App\Controller;

use App\Repository\BaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        // Cache $cache,
    ) {
    }

    protected function getPagninatorProperties(
        Request $request,
        BaseRepository $entityRepository,
        string $defOrderBy = 'id',
        int $defLimit = 50
    ): array {
        $orderBy = $request->query->getString('order_by', $defOrderBy);
        $orderDir = 'ASC' === $request->query->getString('order_dir', 'ASC') ? 'ASC' : 'DESC';
        $limit = $request->query->getInt('limit', $defLimit);
        $page = $request->query->getInt('page', 1);

        if (!\in_array($orderBy, $entityRepository->getFieldNames(), true)) {
            $orderBy = $defOrderBy;
        }

        if (!is_numeric($limit)) {
            $limit = $defLimit;
        }

        if (!is_numeric($page)) {
            $page = 1;
        }

        // limit between 1 and 100
        $limit = min(100, max(1, $limit));
        $page = max(1, $page);

        return [
            $orderBy,
            $orderDir,
            $limit,
            $page,
        ];
    }
}
