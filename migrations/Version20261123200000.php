<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * La date « limite pour libérer le site » du GN (gn.date_fin_orga, jamais relue par le code) devient une étape
 * d'information secrète de la chronologie, puis la colonne est supprimée.
 */
final class Version20261123200000 extends AbstractMigration
{
    private const LIBELLE = 'Date limite pour libérer le site';

    public function getDescription(): string
    {
        return 'gn.date_fin_orga migrée en étape de chronologie (information, secrète), colonne supprimée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "INSERT INTO gn_timeline_step (gn_id, libelle, description, date_prevue, visibilite, type, auto, statut)
             SELECT g.id, :libelle, 'Reprise de l''ancienne date de fin d''occupation du site par l''organisation.', g.date_fin_orga, 'secret', 'info', 0, 'a_faire'
             FROM gn g
             WHERE g.date_fin_orga IS NOT NULL
               AND NOT EXISTS (SELECT 1 FROM gn_timeline_step s WHERE s.gn_id = g.id AND s.libelle = :libelle)",
            ['libelle' => self::LIBELLE],
        );
        $this->addSql('ALTER TABLE gn DROP date_fin_orga');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gn ADD date_fin_orga DATETIME DEFAULT NULL');
        $this->addSql(
            "UPDATE gn g INNER JOIN gn_timeline_step s ON s.gn_id = g.id AND s.libelle = :libelle AND s.type = 'info' SET g.date_fin_orga = s.date_prevue",
            ['libelle' => self::LIBELLE],
        );
        $this->addSql("DELETE FROM gn_timeline_step WHERE libelle = :libelle AND type = 'info'", ['libelle' => self::LIBELLE]);
    }
}
