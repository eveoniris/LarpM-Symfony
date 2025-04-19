<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419213551 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE groupe DROP discord, description_membres'
        );
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE groupe ADD discord VARCHAR(255) DEFAULT NULL, ADD description_membres TEXT DEFAULT NULL'
        );
    }
}
