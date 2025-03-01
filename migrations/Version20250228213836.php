<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228213836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE groupe_gn ADD suzerin_id INT DEFAULT NULL, ADD connetable_id INT DEFAULT NULL, ADD intendant_id INT DEFAULT NULL, ADD navigateur_id INT DEFAULT NULL, ADD camarilla_id INT DEFAULT NULL, ADD bateaux_localisation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C30EF1B89 FOREIGN KEY (suzerin_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11CB42EB948 FOREIGN KEY (connetable_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C7B52884 FOREIGN KEY (intendant_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C7EAA3A12 FOREIGN KEY (navigateur_id) REFERENCES personnage (id)');
        $this->addSql('ALTER TABLE groupe_gn ADD CONSTRAINT FK_413F11C13776DBA FOREIGN KEY (camarilla_id) REFERENCES personnage (id)');
        $this->addSql('CREATE INDEX IDX_413F11C30EF1B89 ON groupe_gn (suzerin_id)');
        $this->addSql('CREATE INDEX IDX_413F11CB42EB948 ON groupe_gn (connetable_id)');
        $this->addSql('CREATE INDEX IDX_413F11C7B52884 ON groupe_gn (intendant_id)');
        $this->addSql('CREATE INDEX IDX_413F11C7EAA3A12 ON groupe_gn (navigateur_id)');
        $this->addSql('CREATE INDEX IDX_413F11C13776DBA ON groupe_gn (camarilla_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE groupe_gn DROP FOREIGN KEY FK_413F11C30EF1B89');
        $this->addSql('ALTER TABLE groupe_gn DROP FOREIGN KEY FK_413F11CB42EB948');
        $this->addSql('ALTER TABLE groupe_gn DROP FOREIGN KEY FK_413F11C7B52884');
        $this->addSql('ALTER TABLE groupe_gn DROP FOREIGN KEY FK_413F11C7EAA3A12');
        $this->addSql('ALTER TABLE groupe_gn DROP FOREIGN KEY FK_413F11C13776DBA');
        $this->addSql('DROP INDEX IDX_413F11C30EF1B89 ON groupe_gn');
        $this->addSql('DROP INDEX IDX_413F11CB42EB948 ON groupe_gn');
        $this->addSql('DROP INDEX IDX_413F11C7B52884 ON groupe_gn');
        $this->addSql('DROP INDEX IDX_413F11C7EAA3A12 ON groupe_gn');
        $this->addSql('DROP INDEX IDX_413F11C13776DBA ON groupe_gn');
        $this->addSql('ALTER TABLE groupe_gn DROP suzerin_id, DROP connetable_id, DROP intendant_id, DROP navigateur_id, DROP camarilla_id, DROP bateaux_localisation');
    }
}
