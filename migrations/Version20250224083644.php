<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250224083644 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bonus ADD CONSTRAINT FK_9F987F7A15761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE693547D0F97A8');
        $this->addSql('DROP INDEX bonus_territoire ON origine_bonus');
        $this->addSql('ALTER TABLE origine_bonus CHANGE status status VARCHAR(36) DEFAULT NULL, CHANGE creation_date creation_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE693547D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id)');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX idx_be69354769545666 TO fk_bonus_idx');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX idx_be693547d0f97a8 TO fk_territoire_idx');
        $this->addSql('ALTER TABLE personnage_bonus DROP INDEX UNIQ_35CB734069545666, ADD INDEX fk_bonus_idx (bonus_id)');
        $this->addSql('ALTER TABLE personnage_bonus CHANGE personnage_id personnage_id INT NOT NULL');
        $this->addSql('ALTER TABLE personnage_bonus RENAME INDEX idx_35cb73405e315342 TO fk_personnage_idx');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE origine_bonus DROP FOREIGN KEY FK_BE693547D0F97A8');
        $this->addSql('ALTER TABLE origine_bonus CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE status status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE origine_bonus ADD CONSTRAINT FK_BE693547D0F97A8 FOREIGN KEY (territoire_id) REFERENCES territoire (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX bonus_territoire ON origine_bonus (bonus_id, territoire_id)');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX fk_bonus_idx TO IDX_BE69354769545666');
        $this->addSql('ALTER TABLE origine_bonus RENAME INDEX fk_territoire_idx TO IDX_BE693547D0F97A8');
        $this->addSql('ALTER TABLE bonus DROP FOREIGN KEY FK_9F987F7A15761DAB');
        $this->addSql('ALTER TABLE personnage_bonus DROP INDEX fk_bonus_idx, ADD UNIQUE INDEX UNIQ_35CB734069545666 (bonus_id)');
        $this->addSql('ALTER TABLE personnage_bonus CHANGE personnage_id personnage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE personnage_bonus RENAME INDEX fk_personnage_idx TO IDX_35CB73405E315342');
        }
}
