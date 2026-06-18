<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260618115912 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige allowed_in_character_creation : active tous les territoires racine sauf les 3 exclus (454, 453, 452)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE territoire SET allowed_in_character_creation = 1 WHERE territoire_id IS NULL');
        $this->addSql('UPDATE territoire SET allowed_in_character_creation = 0 WHERE id IN (454, 453, 452)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE territoire SET allowed_in_character_creation = 0 WHERE territoire_id IS NULL');
    }
}
