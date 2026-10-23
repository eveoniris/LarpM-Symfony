<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dépouillement : étiquettes des objets et des armes (table depouillement_etiquette)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE depouillement_etiquette (id INT AUTO_INCREMENT NOT NULL, nature VARCHAR(10) NOT NULL, couleur VARCHAR(10) NOT NULL, numero INT DEFAULT NULL, qualite VARCHAR(3) DEFAULT NULL, quantite INT DEFAULT 1 NOT NULL, situation VARCHAR(20) NOT NULL, destination VARCHAR(20) NOT NULL, action_objet VARCHAR(20) DEFAULT NULL, speciale TINYINT DEFAULT 0 NOT NULL, a_prevoir_prochain_opus TINYINT DEFAULT 0 NOT NULL, anomalies JSON NOT NULL, remarque LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, depouillement_id INT NOT NULL, item_id INT UNSIGNED DEFAULT NULL, INDEX IDX_400D9262126F525E (item_id), INDEX idx_depouillement_etiquette_depouillement (depouillement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE depouillement_etiquette ADD CONSTRAINT FK_400D9262246EFAD2 FOREIGN KEY (depouillement_id) REFERENCES depouillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depouillement_etiquette ADD CONSTRAINT FK_400D9262126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE depouillement_etiquette DROP FOREIGN KEY FK_400D9262246EFAD2');
        $this->addSql('ALTER TABLE depouillement_etiquette DROP FOREIGN KEY FK_400D9262126F525E');
        $this->addSql('DROP TABLE depouillement_etiquette');
    }
}
