<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261102100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lignes reportees dans l enveloppe du personnage pour le GN suivant (table enveloppe_ligne)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE enveloppe_ligne (id INT AUTO_INCREMENT NOT NULL, nature VARCHAR(30) NOT NULL, libelle VARCHAR(255) NOT NULL, quantite SMALLINT DEFAULT 1 NOT NULL, numero_objet INT DEFAULT NULL, created_at DATETIME NOT NULL, personnage_id INT NOT NULL, gn_id INT NOT NULL, depouillement_id INT DEFAULT NULL, INDEX IDX_D89966DE5E315342 (personnage_id), INDEX IDX_D89966DEAFC9C052 (gn_id), INDEX IDX_D89966DE246EFAD2 (depouillement_id), INDEX idx_enveloppe_ligne_personnage_gn (personnage_id, gn_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE enveloppe_ligne ADD CONSTRAINT FK_D89966DE5E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enveloppe_ligne ADD CONSTRAINT FK_D89966DEAFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enveloppe_ligne ADD CONSTRAINT FK_D89966DE246EFAD2 FOREIGN KEY (depouillement_id) REFERENCES depouillement (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE enveloppe_ligne');
    }
}
