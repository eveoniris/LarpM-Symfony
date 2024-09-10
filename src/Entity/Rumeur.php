<?php

namespace App\Entity;

use App\Repository\RumeurRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RumeurRepository::class)]
class Rumeur extends BaseRumeur
{
    /**
     * Constructeur. Met en place la date de création et de mise à jour de la rumeur.
     */
    public function __construct()
    {
        $this->setCreationDate(new \DateTime('NOW'));
        $this->setUpdateDate(new \DateTime('NOW'));
        parent::__construct();
    }

    public function getVisibility(): string
    {
        return match (parent::getVisibility()) {
            'disponible' => 'Disponible pour les joueurs',
            default => 'Brouillon',
        };
    }

    public function getLabel(): string
    {
        return 'Par '.$this->user->getDisplayName().' en '.$this->territoire->getNom();
    }

    public function getDescription(): ?string
    {
        return $this->getText() ?? '';
    }
}
