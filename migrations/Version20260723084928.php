<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723084928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmente la longueur de personnage.trombineUrl (45 -> 255) pour éviter les erreurs SQL 1406 lors de l\'upload de photo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage CHANGE trombineUrl trombineUrl VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage CHANGE trombineUrl trombineUrl VARCHAR(45) DEFAULT NULL');
    }
}
