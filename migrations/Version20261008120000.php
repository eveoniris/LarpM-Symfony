<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HelloAsso Plus Billetterie : gn.helloasso_event_id, billet.helloasso_product_id, table billet_sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD helloasso_event_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE billet ADD helloasso_product_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE TABLE billet_sync (
            id INT AUTO_INCREMENT NOT NULL,
            gn_id INT NOT NULL,
            user_id INT UNSIGNED DEFAULT NULL,
            billet_id INT DEFAULT NULL,
            attendee_id VARCHAR(64) NOT NULL,
            order_id VARCHAR(64) DEFAULT NULL,
            product_id VARCHAR(64) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            nom VARCHAR(255) DEFAULT NULL,
            status VARCHAR(32) DEFAULT NULL,
            price INT DEFAULT NULL,
            confiance VARCHAR(16) NOT NULL,
            etat VARCHAR(16) NOT NULL,
            raw_data JSON DEFAULT NULL,
            synchronise_le DATETIME NOT NULL,
            valide_le DATETIME DEFAULT NULL,
            INDEX IDX_8DCF2802AFC9C052 (gn_id),
            INDEX IDX_8DCF2802A76ED395 (user_id),
            INDEX IDX_8DCF280244973C78 (billet_id),
            UNIQUE INDEX uniq_billet_sync_attendee (gn_id, attendee_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE billet_sync ADD CONSTRAINT FK_BILLET_SYNC_GN FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE billet_sync ADD CONSTRAINT FK_BILLET_SYNC_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE billet_sync ADD CONSTRAINT FK_BILLET_SYNC_BILLET FOREIGN KEY (billet_id) REFERENCES billet (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE billet_sync');
        $this->addSql('ALTER TABLE billet DROP helloasso_product_id');
        $this->addSql('ALTER TABLE gn DROP helloasso_event_id');
    }
}
