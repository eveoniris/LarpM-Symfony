<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250221215610 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe_bonus DROP FOREIGN KEY FK_CA35B12A7A45358C');
        $this->addSql('ALTER TABLE groupe_bonus DROP FOREIGN KEY FK_CA35B12A69545666');
        $this->addSql('ALTER TABLE personnage_bonus DROP FOREIGN KEY FK_35CB73405E315342');
        $this->addSql('ALTER TABLE personnage_bonus DROP FOREIGN KEY FK_35CB734069545666');
        $this->addSql('DROP TABLE groupe_bonus');
        $this->addSql('DROP TABLE personnage_bonus');
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE69354769545666');
        $this->addSql('ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE69354723BFF59E FOREIGN KEY (bonus_id) REFERENCES bonus (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX idx_be69354769545666 TO IDX_BE69354723BFF59E');

    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE groupe_bonus (id INT AUTO_INCREMENT NOT NULL, groupe_id INT NOT NULL, bonus_id INT NOT NULL, creation_date DATETIME NOT NULL, status VARCHAR(32) not null,INDEX fk_bonus_idx (bonus_id), INDEX fk_groupe_idx (groupe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnage_bonus (id INT AUTO_INCREMENT NOT NULL, personnage_id INT DEFAULT NULL, bonus_id INT NOT NULL, creation_date DATETIME DEFAULT NULL, status VARCHAR(36) DEFAULT NULL, INDEX IDX_35CB73405E315342 (personnage_id), INDEX UNIQ_35CB734069545666 (bonus_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE groupe_bonus ADD CONSTRAINT FK_CA35B12A7A45358C FOREIGN KEY (groupe_id) REFERENCES groupe (id)');
        $this->addSql('ALTER TABLE groupe_bonus ADD CONSTRAINT FK_CA35B12A69545666 FOREIGN KEY (bonus_id) REFERENCES bonus (id)');
        $this->addSql('ALTER TABLE personnage_bonus ADD CONSTRAINT FK_35CB73405E315342 FOREIGN KEY (personnage_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE personnage_bonus ADD CONSTRAINT FK_35CB734069545666 FOREIGN KEY (bonus_id) REFERENCES bonus (id)');
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE69354723BFF59E');
        $this->addSql('ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE69354769545666 FOREIGN KEY (bonus_id) REFERENCES bonus (id)');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX idx_be69354723bff59e TO IDX_BE69354769545666');


    }
}
