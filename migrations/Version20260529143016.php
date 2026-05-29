<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529143016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Expand documentUrl columns from VARCHAR(45) to VARCHAR(255) to prevent truncation errors';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence CHANGE documentUrl documentUrl LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE connaissance CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE debriefing CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE document CHANGE documentUrl documentUrl VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE langue CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE loi CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE potion CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE priere CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sort CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sorts CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE technologie CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE competence CHANGE documentUrl documentUrl TINYTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE connaissance CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE debriefing CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE document CHANGE documentUrl documentUrl VARCHAR(45) NOT NULL');
        $this->addSql('ALTER TABLE langue CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE loi CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE potion CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE priere CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE sort CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE sorts CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
        $this->addSql('ALTER TABLE technologie CHANGE documentUrl documentUrl VARCHAR(45) DEFAULT NULL');
    }
}
