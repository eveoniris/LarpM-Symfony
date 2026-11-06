<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'api_client : périmètre GN (gn_id) pour les applications en lecture seule (ROLE_API_READ)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_client ADD gn_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE api_client ADD CONSTRAINT FK_41B343D5AFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_41B343D5AFC9C052 ON api_client (gn_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_client DROP FOREIGN KEY FK_41B343D5AFC9C052');
        $this->addSql('DROP INDEX IDX_41B343D5AFC9C052 ON api_client');
        $this->addSql('ALTER TABLE api_client DROP gn_id');
    }
}
