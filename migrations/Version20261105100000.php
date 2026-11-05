<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tables discord_guild et discord_role_mapping (guildes Discord et correspondance des roles)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE discord_guild (id INT AUTO_INCREMENT NOT NULL, guild_id VARCHAR(32) NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_7539ABA85F2131EF (guild_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE discord_role_mapping (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, role_id VARCHAR(32) NOT NULL, numero INT DEFAULT NULL, category_id VARCHAR(32) DEFAULT NULL, channel_id VARCHAR(32) DEFAULT NULL, guild_id INT NOT NULL, secondary_group_id INT DEFAULT NULL, INDEX IDX_F466EE605F2131EF (guild_id), INDEX IDX_F466EE6065F50501 (secondary_group_id), UNIQUE INDEX uniq_discord_mapping_guild_groupe (guild_id, secondary_group_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE discord_role_mapping ADD CONSTRAINT FK_DRM_GUILD FOREIGN KEY (guild_id) REFERENCES discord_guild (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discord_role_mapping ADD CONSTRAINT FK_DRM_GROUP FOREIGN KEY (secondary_group_id) REFERENCES secondary_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE discord_role_mapping');
        $this->addSql('DROP TABLE discord_guild');
    }
}
