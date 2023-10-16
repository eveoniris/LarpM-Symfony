<?php

namespace App\Repository;

class LikeExpression
{
    protected $key;

    public function __construct(protected $column, protected $value)
    {
        $this->key = 'key'.preg_replace('/[^a-zA-Z]/', '_', (string) $column);
    }

    public function apply($qb): void
    {
        $qb->andWhere($this->column.' like :'.$this->key);
        $qb->setParameter($this->key, $this->value);
    }
}
