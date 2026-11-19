<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Message extends BaseMessage
{
    public const string SYSTEM_AUTEUR_LABEL = 'Équipe de gestion';

    public function __construct()
    {
        parent::__construct();
        $this->setCreationDate(new DateTime('NOW'));
        $this->setUpdateDate(new DateTime('NOW'));
    }

    /** Message système : aucun auteur, on ne peut pas y répondre. */
    public function isSystem(): bool
    {
        return null === $this->getUserRelatedByAuteur();
    }

    /** Nom affiché pour l'expéditeur : personnage, sinon nom d'utilisateur, sinon « Équipe de gestion ». */
    public function getAuteurLabel(): string
    {
        $auteur = $this->getUserRelatedByAuteur();
        if (null === $auteur) {
            return self::SYSTEM_AUTEUR_LABEL;
        }

        return $auteur->getPersonnage()?->getPublicName() ?? $auteur->getUserName();
    }
}
