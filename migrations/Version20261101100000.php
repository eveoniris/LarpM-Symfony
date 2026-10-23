<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Classe « secrète » des héritiers nés en jeu : indicateur sur classe, et la classe elle-même.
 *
 * Elle n'est pas proposée à la création (creation = 0) et un personnage qui la porte ne peut pas être repris par un joueur
 * tant que sa classe n'a pas été changée.
 */
final class Version20261101100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Indicateur secrete sur classe et classe secrète des héritiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE classe ADD secrete TINYINT DEFAULT 0 NOT NULL');
        $this->addSql(<<<'SQL'
            INSERT INTO classe (label_masculin, label_feminin, description, discr, creation, secrete)
            SELECT 'Secret', 'Secrète', 'Classe provisoire d''un héritier né en jeu : à changer avant que le personnage soit repris par un joueur.', 'extended', 0, 1
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM classe WHERE secrete = 1)
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Échoue volontairement si des personnages portent encore la classe secrète (clé étrangère).
        $this->addSql('DELETE FROM classe WHERE secrete = 1');
        $this->addSql('ALTER TABLE classe DROP secrete');
    }
}
