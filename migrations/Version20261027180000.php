<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HelloAsso : table billet_tarif (produit + prix -> billet, plusieurs produits par billet et inversement)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE billet_tarif (
            id INT AUTO_INCREMENT NOT NULL,
            gn_id INT NOT NULL,
            billet_id INT NOT NULL,
            product_id VARCHAR(64) NOT NULL,
            price INT DEFAULT NULL,
            prioritaire TINYINT(1) DEFAULT 0 NOT NULL,
            INDEX IDX_500E941AAFC9C052 (gn_id),
            INDEX IDX_500E941A44973C78 (billet_id),
            UNIQUE INDEX uniq_billet_tarif (gn_id, product_id, price),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE billet_tarif ADD CONSTRAINT FK_BILLET_TARIF_GN FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE billet_tarif ADD CONSTRAINT FK_BILLET_TARIF_BILLET FOREIGN KEY (billet_id) REFERENCES billet (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE billet_tarif');
    }
}
