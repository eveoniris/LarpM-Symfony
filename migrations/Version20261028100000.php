<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261028100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dépouillement : photos des enveloppes (table depouillement_photo)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE depouillement_photo (id INT AUTO_INCREMENT NOT NULL, chemin VARCHAR(255) NOT NULL, nom_original VARCHAR(255) NOT NULL, mime_type VARCHAR(50) NOT NULL, taille INT NOT NULL, televerse_at DATETIME NOT NULL, partage TINYINT DEFAULT 0 NOT NULL, depouillement_id INT NOT NULL, televerse_par_id INT UNSIGNED DEFAULT NULL, INDEX IDX_C7E67B6D48179CB9 (televerse_par_id), INDEX idx_depouillement_photo_depouillement (depouillement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE depouillement_photo ADD CONSTRAINT FK_C7E67B6D246EFAD2 FOREIGN KEY (depouillement_id) REFERENCES depouillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depouillement_photo ADD CONSTRAINT FK_C7E67B6D48179CB9 FOREIGN KEY (televerse_par_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE depouillement_photo DROP FOREIGN KEY FK_C7E67B6D246EFAD2');
        $this->addSql('ALTER TABLE depouillement_photo DROP FOREIGN KEY FK_C7E67B6D48179CB9');
        $this->addSql('DROP TABLE depouillement_photo');
    }
}
