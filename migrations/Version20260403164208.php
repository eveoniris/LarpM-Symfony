<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403164208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE inter_jeu (id INT UNSIGNED AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, annee_jeu INT NOT NULL, date_reel DATE NOT NULL, etat VARCHAR(50) NOT NULL, information_complementaire LONGTEXT DEFAULT NULL, discr VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inter_jeu_personnage (inter_jeu_id INT UNSIGNED NOT NULL, personnage_id INT NOT NULL, INDEX IDX_73398035236E5BFF (inter_jeu_id), INDEX IDX_733980355E315342 (personnage_id), PRIMARY KEY (inter_jeu_id, personnage_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE inter_jeu_personnage ADD CONSTRAINT FK_73398035236E5BFF FOREIGN KEY (inter_jeu_id) REFERENCES inter_jeu (id)');
        $this->addSql('ALTER TABLE inter_jeu_personnage ADD CONSTRAINT FK_733980355E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inter_jeu_personnage DROP FOREIGN KEY FK_73398035236E5BFF');
        $this->addSql('ALTER TABLE inter_jeu_personnage DROP FOREIGN KEY FK_733980355E315342');
        $this->addSql('DROP TABLE inter_jeu');
        $this->addSql('DROP TABLE inter_jeu_personnage');
    }
}
