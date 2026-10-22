<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261027100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dépouillement des enveloppes de retour de jeu : tables depouillement et depouillement_history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE depouillement (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, numero VARCHAR(30) NOT NULL, statut VARCHAR(20) NOT NULL, simulation TINYINT DEFAULT 0 NOT NULL, donnees JSON NOT NULL, remarques LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, validated_at DATETIME DEFAULT NULL, gn_id INT NOT NULL, participant_id INT DEFAULT NULL, groupe_gn_id INT DEFAULT NULL, depouilleur_id INT UNSIGNED DEFAULT NULL, validated_by_id INT UNSIGNED DEFAULT NULL, INDEX IDX_932519C2AFC9C052 (gn_id), INDEX IDX_932519C29D1C3019 (participant_id), INDEX IDX_932519C2FA640E02 (groupe_gn_id), INDEX IDX_932519C2AABF9C97 (depouilleur_id), INDEX IDX_932519C2C69DE5E5 (validated_by_id), INDEX idx_depouillement_enveloppe (gn_id, type, numero), INDEX idx_depouillement_statut (statut), INDEX idx_depouillement_simulation (simulation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE depouillement_history (id INT AUTO_INCREMENT NOT NULL, action_date DATETIME NOT NULL, action_type VARCHAR(20) NOT NULL, statut_avant VARCHAR(20) DEFAULT NULL, statut_apres VARCHAR(20) DEFAULT NULL, data_before JSON DEFAULT NULL, data_after JSON DEFAULT NULL, motif LONGTEXT DEFAULT NULL, depouillement_id INT NOT NULL, user_id INT UNSIGNED DEFAULT NULL, INDEX IDX_58F0C124A76ED395 (user_id), INDEX idx_depouillement_history_depouillement (depouillement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE depouillement ADD CONSTRAINT FK_932519C2AFC9C052 FOREIGN KEY (gn_id) REFERENCES gn (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depouillement ADD CONSTRAINT FK_932519C29D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depouillement ADD CONSTRAINT FK_932519C2FA640E02 FOREIGN KEY (groupe_gn_id) REFERENCES groupe_gn (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depouillement ADD CONSTRAINT FK_932519C2AABF9C97 FOREIGN KEY (depouilleur_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depouillement ADD CONSTRAINT FK_932519C2C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE depouillement_history ADD CONSTRAINT FK_58F0C124246EFAD2 FOREIGN KEY (depouillement_id) REFERENCES depouillement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depouillement_history ADD CONSTRAINT FK_58F0C124A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE depouillement DROP FOREIGN KEY FK_932519C2AFC9C052');
        $this->addSql('ALTER TABLE depouillement DROP FOREIGN KEY FK_932519C29D1C3019');
        $this->addSql('ALTER TABLE depouillement DROP FOREIGN KEY FK_932519C2FA640E02');
        $this->addSql('ALTER TABLE depouillement DROP FOREIGN KEY FK_932519C2AABF9C97');
        $this->addSql('ALTER TABLE depouillement DROP FOREIGN KEY FK_932519C2C69DE5E5');
        $this->addSql('ALTER TABLE depouillement_history DROP FOREIGN KEY FK_58F0C124246EFAD2');
        $this->addSql('ALTER TABLE depouillement_history DROP FOREIGN KEY FK_58F0C124A76ED395');
        $this->addSql('DROP TABLE depouillement');
        $this->addSql('DROP TABLE depouillement_history');
    }
}
