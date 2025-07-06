<?php

namespace App\Entity;

use App\Repository\RenommeHistoryRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RenommeHistoryRepository::class)]
class RenommeHistory extends BaseRenommeHistory
{
}
