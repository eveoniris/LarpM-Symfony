<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Tranche d'âge « Enfant héritier » (0 à 14 ans) pour les héritiers nés en jeu.
 *
 * Les tranches de départ commencent à « Jeune » (15 ans) : sans cette ligne, un héritier de 0 an n'est retrouvé par aucune
 * tranche et le vieillissement ne le met pas à jour. Ce n'est PAS un enfant de la partie enfants (jeu, fiches et groupe distincts) :
 * un héritier n'est pas jouable en adulte tant qu'il n'est pas attribué par un orga ou un scénariste.
 */
final class Version20261030100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tranche d\'âge « Enfant héritier » (0 an, non choisissable à la création)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO age (label, description, bonus, enableCreation, discr, minimumValue)
            SELECT 'Enfant héritier', 'Héritier né en jeu, de 0 à 14 ans. Pas jouable en adulte avant d''être attribué (distinct des enfants de la partie enfants).', 0, 0, 'extended', 0
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM age WHERE label = 'Enfant héritier')
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Échoue volontairement si des personnages utilisent encore cette tranche (clé étrangère).
        $this->addSql("DELETE FROM age WHERE label = 'Enfant héritier'");
    }
}
