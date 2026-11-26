<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Inter-jeu : statut des participants (inscrit, en attente, désisté), propositions des orgas externes
 * et table d'accès des orgas externes. Les lignes existantes deviennent « inscrit ».
 */
final class Version20261126100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Statut des participants d\'inter-jeu et accès des orgas externes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "ALTER TABLE inter_jeu_personnage ADD statut VARCHAR(20) DEFAULT 'inscrit' NOT NULL, ADD statut_propose VARCHAR(20) DEFAULT NULL, ADD propose_par_id INT UNSIGNED DEFAULT NULL, ADD ajout_propose TINYINT(1) DEFAULT 0 NOT NULL",
        );
        $this->addSql('CREATE INDEX IDX_73398035168A12A2 ON inter_jeu_personnage (propose_par_id)');
        $this->addSql('ALTER TABLE inter_jeu_personnage ADD CONSTRAINT FK_73398035168A12A2 FOREIGN KEY (propose_par_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql(
            'CREATE TABLE inter_jeu_orga (id INT UNSIGNED AUTO_INCREMENT NOT NULL, inter_jeu_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, valide_par_id INT UNSIGNED DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_38D682F236E5BFF (inter_jeu_id), INDEX IDX_38D682FA76ED395 (user_id), INDEX IDX_38D682F6AF12ED9 (valide_par_id), UNIQUE INDEX uniq_inter_jeu_orga (inter_jeu_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
        );
        $this->addSql('ALTER TABLE inter_jeu_orga ADD CONSTRAINT FK_38D682F236E5BFF FOREIGN KEY (inter_jeu_id) REFERENCES inter_jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inter_jeu_orga ADD CONSTRAINT FK_38D682FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inter_jeu_orga ADD CONSTRAINT FK_38D682F6AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inter_jeu_orga');
        $this->addSql('ALTER TABLE inter_jeu_personnage DROP FOREIGN KEY FK_73398035168A12A2');
        $this->addSql('DROP INDEX IDX_73398035168A12A2 ON inter_jeu_personnage');
        $this->addSql('ALTER TABLE inter_jeu_personnage DROP statut, DROP statut_propose, DROP propose_par_id, DROP ajout_propose');
    }
}
