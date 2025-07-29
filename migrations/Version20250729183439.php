<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729183439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
              CREATE TABLE qr_code_scan_log (id INT AUTO_INCREMENT NOT NULL, user_id INT UNSIGNED NOT NULL, participant_id INT DEFAULT NULL, item_id INT UNSIGNED DEFAULT NULL, date DATETIME NOT NULL, allowed TINYINT(1) NOT NULL, INDEX IDX_4F1CE7B0A76ED395 (user_id), INDEX IDX_4F1CE7B09D1C3019 (participant_id), INDEX IDX_4F1CE7B0126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
          SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log ADD CONSTRAINT FK_4F1CE7B0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log ADD CONSTRAINT FK_4F1CE7B09D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log ADD CONSTRAINT FK_4F1CE7B0126F525E FOREIGN KEY (item_id) REFERENCES item (id)
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE participant ADD couchage VARCHAR(32) DEFAULT NULL, ADD special LONGTEXT DEFAULT NULL
        SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log DROP FOREIGN KEY FK_4F1CE7B0A76ED395
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log DROP FOREIGN KEY FK_4F1CE7B09D1C3019
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE qr_code_scan_log DROP FOREIGN KEY FK_4F1CE7B0126F525E
        SQL
        );
        $this->addSql(<<<'SQL'
            DROP TABLE qr_code_scan_log
        SQL
        );
        $this->addSql(<<<'SQL'
            ALTER TABLE participant DROP couchage, DROP special
        SQL
        );
    }
}
