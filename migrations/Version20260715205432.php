<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715205432 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table groupe_gn_demande (candidatures joueur->groupe et invitations chef->joueur).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE groupe_gn_demande (id INT AUTO_INCREMENT NOT NULL, date DATETIME DEFAULT NULL, type VARCHAR(20) NOT NULL, message LONGTEXT DEFAULT NULL, participant_id INT NOT NULL, groupe_gn_id INT NOT NULL, discr VARCHAR(255) NOT NULL, INDEX fk_groupe_gn_demande_participant1_idx (participant_id), INDEX fk_groupe_gn_demande_groupe_gn1_idx (groupe_gn_id), UNIQUE INDEX uniq_groupe_gn_demande (participant_id, groupe_gn_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE groupe_gn_demande ADD CONSTRAINT FK_CB03F5249D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id)');
        $this->addSql('ALTER TABLE groupe_gn_demande ADD CONSTRAINT FK_CB03F524FA640E02 FOREIGN KEY (groupe_gn_id) REFERENCES groupe_gn (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groupe_gn_demande DROP FOREIGN KEY FK_CB03F5249D1C3019');
        $this->addSql('ALTER TABLE groupe_gn_demande DROP FOREIGN KEY FK_CB03F524FA640E02');
        $this->addSql('DROP TABLE groupe_gn_demande');
    }
}
