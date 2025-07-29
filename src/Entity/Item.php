<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: ItemRepository::class)]
class Item extends BaseItem
{
    public function __construct()
    {
        $this->setDateCreation(new DateTime('NOW'));
        $this->setDateUpdate(new DateTime('NOW'));
        $this->setQuantite(1);
        parent::__construct();
    }

    /**
     * Fourni un tableau pour exporter l'objet dans un fichier CSV.
     */
    public function getExportValue(): array
    {
        $objet = $this->getObjet();

        return [
            'numero' => $this->getNumero(),
            'qualite' => $this->getQuality()->getNumero(),
            'identification' => $this->getIdentification(),
            'qualident' => $this->getQualident(),
            'couleur' => $this->getCouleur(),
            'label' => $this->getLabel(),
            'description' => html_entity_decode(strip_tags((string)$this->getDescription())),
            'special' => html_entity_decode(strip_tags((string)$this->getSpecial())),
            'groupe' => $this->getGroupesAsString(),
            'personnage' => $this->getPersonnageAsString(),
            'rangement' => $objet?->getRangement()?->getAdresse(),
            'proprietaire' => $objet?->getProprietaire()?->getNom(),
        ];
    }

    public function getQualident(): int
    {
        return sprintf('%s%s', $this->getQuality()->getNumero(), $this->getIdentification());
    }

    public function getGroupesAsString(): string
    {
        $string = '';
        foreach ($this->getGroupes() as $groupe) {
            $string .= $groupe->getNom() . ', ';
        }

        return $string;
    }

    public function getPersonnageAsString(): string
    {
        $string = '';
        foreach ($this->getPersonnages() as $personnage) {
            $string .= $personnage->getNom() . ', ';
        }

        return $string;
    }

    public function getIdentite(): string
    {
        return $this->getNumero() . ' - ' . $this->getLabel();
    }

    public function getIdentiteReverse(): string
    {
        return $this->getLabel() . ' (' . $this->getNumero() . ')';
    }
}
