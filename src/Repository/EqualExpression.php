<?php

declare(strict_types=1);

namespace App\Repository;

class EqualExpression
{
    protected string $key;

    public function __construct(
        protected string $column,
        protected mixed $value,
    ) {
        $this->key = 'key' . preg_replace('/[^a-zA-Z]/', '_', (string) $column);
    }

    public function apply(\Doctrine\ORM\QueryBuilder $qb): void
    {
        $qb->andWhere(\sprintf('%s = :%s', $this->column, $this->key));
        $qb->setParameter($this->key, $this->value);
    }
}
