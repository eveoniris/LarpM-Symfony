<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260602000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Fix message.discr: migrate legacy 'Message' discriminator to 'extended' (Doctrine STI map change)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE message SET discr = 'extended' WHERE discr = 'Message'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE message SET discr = 'Message' WHERE discr = 'extended'");
    }
}
