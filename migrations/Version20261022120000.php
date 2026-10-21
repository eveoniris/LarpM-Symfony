<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261022120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'personnage_trigger : suppression logique, GN d\'origine et choix consommé';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE personnage_trigger ADD created_at DATETIME DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL, ADD choice_label VARCHAR(255) DEFAULT NULL, ADD gn_id INT DEFAULT NULL, ADD potion_id INT DEFAULT NULL, ADD sort_id INT DEFAULT NULL',
        );
        $this->addSql('ALTER TABLE personnage_trigger ADD CONSTRAINT FK_3674375CAFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE personnage_trigger ADD CONSTRAINT FK_3674375C7126B348 FOREIGN KEY (potion_id) REFERENCES potion (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE personnage_trigger ADD CONSTRAINT FK_3674375C47013001 FOREIGN KEY (sort_id) REFERENCES sort (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3674375CAFC9C052 ON personnage_trigger (gn_id)');
        $this->addSql('CREATE INDEX IDX_3674375C7126B348 ON personnage_trigger (potion_id)');
        $this->addSql('CREATE INDEX IDX_3674375C47013001 ON personnage_trigger (sort_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE personnage_trigger DROP FOREIGN KEY FK_3674375CAFC9C052');
        $this->addSql('ALTER TABLE personnage_trigger DROP FOREIGN KEY FK_3674375C7126B348');
        $this->addSql('ALTER TABLE personnage_trigger DROP FOREIGN KEY FK_3674375C47013001');
        $this->addSql('DROP INDEX IDX_3674375CAFC9C052 ON personnage_trigger');
        $this->addSql('DROP INDEX IDX_3674375C7126B348 ON personnage_trigger');
        $this->addSql('DROP INDEX IDX_3674375C47013001 ON personnage_trigger');
        $this->addSql('ALTER TABLE personnage_trigger DROP created_at, DROP deleted_at, DROP choice_label, DROP gn_id, DROP potion_id, DROP sort_id');
    }
}
