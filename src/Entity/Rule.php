<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'App\Repository\BaseRuleRepository')]
class Rule extends BaseRule
{
}
