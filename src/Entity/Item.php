<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;

#[Entity(repositoryClass: ItemRepository::class)]
class Item extends BaseItem
{

    public function __construct()
    {
        $this->setDateCreation(new \DateTime('NOW'));
        $this->setDateUpdate(new \DateTime('NOW'));
        $this->setQuantite(1);
        parent::__construct();
    }

    public function getIdentite(): string
    {
        return $this->getNumero().' - '.$this->getLabel();
    }

    public function getIdentiteReverse(): string
    {
        return $this->getLabel().' ('.$this->getNumero().')';
    }

    public function getQualident(): int
    {
        return $this->getQuality()->getNumero().$this->getIdentification();

    }
}
