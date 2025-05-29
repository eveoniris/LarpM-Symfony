<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528230133 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE item DROP description_secrete, DROP description_scenariste, CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL, CHANGE quality_id quality_id INT NOT NULL, CHANGE statut_id statut_id INT DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE espece DROP description_secrete
        SQL,
        );
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            <<<'SQL'
            ALTER TABLE espece ADD description_secrete LONGTEXT DEFAULT NULL
        SQL,
        );
        $this->addSql(
            <<<'SQL'
            ALTER TABLE item ADD description_secrete LONGTEXT DEFAULT NULL, ADD description_scenariste LONGTEXT DEFAULT NULL
        SQL,
        );
    }
}
