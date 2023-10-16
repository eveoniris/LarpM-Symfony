<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\BaseRuleRepository')]
class Rule extends BaseRule
{
}
