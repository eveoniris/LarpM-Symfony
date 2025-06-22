<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OrderBy
{
    public const ASC = 'ASC';
    public const DESC = 'DESC'; // or minus

    // default sorting
    protected ?string $sort = null;
    /** @deprecated */
    protected ?string $orderBy = null;
    protected ?string $alias = null;

    private array $orders = [];

    public function __construct(protected RequestStack $requestStack)
    {
        $this->getFromRequest();
    }

    public function getFromRequest(
        ?Request $request = null,
        ?string $alias = null,
    ): self {
        $request ??= $this->requestStack->getCurrentRequest();

        if (!$request) {
            return $this;
        }

        $this->alias ??= $alias;

        $this->getRequestOrderDir($request);
        $this->getRequestOrderBy($request);

        return $this;
    }

    protected function getRequestOrderDir(?Request $request = null): string
    {
        $request ??= $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $this->sort ?? self::ASC;
        }

        $orderDir = strtoupper($request->query->getString('order_dir')) ?: null;

        if ($orderDir && $this->isAllowed($orderDir)) {
            $this->sort = $orderDir;
        }

        return $this->sort ?? self::ASC;
    }

    protected function isAllowed($value): bool
    {
        return \in_array($value, [self::ASC, self::DESC], true);
    }

    protected function getRequestOrderBy(
        ?Request $request = null,
        ?string $alias = null,
    ): ?string {
        $request ??= $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $this->orderBy = $request->query->getString('order_by')
            ?: $request->get('order_by') ?: null;

        if (!$this->orderBy) {
            return null;
        }

        $alias ??= $this->alias;
        $multiOrder = explode(',', $this->orderBy);
        if (!empty($multiOrder)) {
            // reset default
            $this->orders = [];

            foreach ($multiOrder as $order) {
                $this->addOrderBy(
                    ($alias ? $alias.'.' : '').trim($order, '-'),
                    str_starts_with($order, '-') ? self::DESC : $this->sort,
                );
            }
        }

        if ($alias) {
            $this->orderBy = $alias.'.'.$this->orderBy;
        }

        return $this->orderBy;
    }

    public function addOrderBy(string $orderBy, ?string $sort = null): self
    {
        $sort ??= $this->sort;

        if ('-' === $sort) {
            $sort = self::DESC;
        }

        $this->orders[$orderBy] = $this->isAllowed($sort) ? $sort : self::ASC;

        return $this;
    }

    public function addOrderToQuery(QueryBuilder $queryBuilder): void
    {
        foreach ($this->orders as $order => $sort) {
            $queryBuilder->addOrderBy($order, $sort);
        }
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Default is ASC, then is DESC, then is "not in order".
     */
    public function getNextDirForLink(?string $value): ?string
    {
        if (self::ASC === $value) {
            return self::DESC;
        }

        if (self::DESC === $value || '-' === $value) {
            return null;
        }

        return self::ASC;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function setOrders(array $ordersBy): self
    {
        $this->orders = $ordersBy;

        //  dd($this->orders);

        return $this;
    }

    public function getSort(): string
    {
        if (empty($this->sort)) {
            $this->getRequestOrderDir();
        }

        return $this->sort ?? self::ASC;
    }

    public function invertDirForLink(string $value): string
    {
        return self::ASC === $this->invertDir($value) ? '' : '-';
    }

    public function invertDir($value): string
    {
        if (empty($value) || self::DESC === $value || '-' === $value) {
            return self::ASC;
        }

        return self::DESC;
    }

    public function removeOrderBy(string $orderBy): self
    {
        if (isset($this->orders[$orderBy])) {
            unset($this->orders[$orderBy]);
        }

        return $this;
    }

    public function setDefaultOrderDir(string $default): self
    {
        $default = strtoupper($default);
        if ($this->isAllowed($default)) {
            $this->sort = $default;
        }

        return $this;
    }
}
