<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OrderBy
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    protected ?string $sort = null;
    protected ?string $orderBy = null;
    protected ?string $alias = null;

    public function __construct(protected RequestStack $requestStack)
    {
        $this->getFromRequest();
    }

    public function getFromRequest(
        Request $request = null,
        string $alias = null
    ): self {
        $request ??= $this->requestStack?->getCurrentRequest();

        if (!$request) {
            return $this;
        }

        $this->alias ??= $alias;

        $this->getRequestOrderDir($request);
        $this->getRequestOrderBy($request);

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

    protected function getRequestOrderDir(Request $request = null): string
    {
        $request ??= $this->requestStack?->getCurrentRequest();
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
        Request $request = null,

        string $alias = null,
    ): ?string {
        $request ??= $this->requestStack?->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $this->orderBy = $request->query->getString('order_by') ?: null;

        if (!$this->orderBy) {
            return null;
        }

        $alias ??= $this->alias;

        if ($alias) {
            $this->orderBy = $alias.'.'.$this->orderBy;
        }

        return $this->orderBy;
    }

    public function getSort(): string
    {
        if (empty($this->sort)) {
            $this->getRequestOrderDir();
        }

        return $this->sort ?? self::ASC;
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
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
}
