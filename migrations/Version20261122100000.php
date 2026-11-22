<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261122100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Univers scénaristes : chronologie du monde (frise) et échelle de puissance des créatures, avec reprise du classeur d'aide à la rédaction de BG";
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE frise_age (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, periode VARCHAR(255) DEFAULT NULL, ordre INT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql(
            'CREATE TABLE frise_entree (id INT AUTO_INCREMENT NOT NULL, age_id INT NOT NULL, titre VARCHAR(255) NOT NULL, notes LONGTEXT DEFAULT NULL, ordre INT DEFAULT 0 NOT NULL, INDEX IDX_5BFFD59BCC80CD12 (age_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );
        $this->addSql('ALTER TABLE frise_entree ADD CONSTRAINT FK_FRISE_ENTREE_AGE FOREIGN KEY (age_id) REFERENCES frise_age (id) ON DELETE CASCADE');
        $this->addSql(
            'CREATE TABLE creature_puissance (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, famille VARCHAR(255) DEFAULT NULL, xp INT DEFAULT 0 NOT NULL, notes LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4',
        );

        // Reprise des deux feuilles du classeur (Frise, puissance relative)
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Premier Âge des Grands Anciens', '-1M à -500 000', 1)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Second Âge des Anciens', '-500 000 à -100 000', 2)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Premier Âge Thurien', '-100 000 à -50 000', 3)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Second Âge Thurien', '-50 000 à -20 000', 4)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Troisième Âge Thurien', '-20 000 à -18 000', 5)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Âge Post Cataclysme', '-18 000 à -15 500', 6)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Âge Achéronien', '-15 500 à -13 000', 7)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Âge Hyborien', '-13 000 à -8000', 8)");
        $this->addSql("INSERT INTO frise_age (nom, periode, ordre) VALUES ('Âge de Conan', NULL, 9)");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Atlantide', NULL, 1 FROM frise_age WHERE ordre = 1");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Khari', NULL, 2 FROM frise_age WHERE ordre = 1");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'roi géants', NULL, 3 FROM frise_age WHERE ordre = 2");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Salamandres', NULL, 4 FROM frise_age WHERE ordre = 2");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Hommes serpents', NULL, 5 FROM frise_age WHERE ordre = 1");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Hibernation', NULL, 6 FROM frise_age WHERE ordre = 2");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Hibernation', NULL, 7 FROM frise_age WHERE ordre = 7");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Zhemri', NULL, 8 FROM frise_age WHERE ordre = 3");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, '7 royaumes humains', NULL, 9 FROM frise_age WHERE ordre = 3");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Lémure', NULL, 10 FROM frise_age WHERE ordre = 4");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Valusie', NULL, 11 FROM frise_age WHERE ordre = 5");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Démons', NULL, 12 FROM frise_age WHERE ordre = 6");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Solars', NULL, 13 FROM frise_age WHERE ordre = 6");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Sygie', NULL, 14 FROM frise_age WHERE ordre = 7");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Hyborée', NULL, 15 FROM frise_age WHERE ordre = 7");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Zamora', NULL, 16 FROM frise_age WHERE ordre = 7");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Khitaï', NULL, 17 FROM frise_age WHERE ordre = 7");
        $this->addSql("INSERT INTO frise_entree (age_id, titre, notes, ordre) SELECT id, 'Turan', NULL, 18 FROM frise_age WHERE ordre = 8");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Homme rat', NULL, 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Squelette', 'Morts vivants', 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Momies', 'Morts vivants', 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Draught', 'Créature synthétique', 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Fantome', 'Morts vivants', 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Ombre', 'Ombre', 20, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Rêveur', 'Homme serpent', 35, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Hybride Profond', NULL, 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Sirène', NULL, 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Profond', NULL, 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Ombrelin', 'Ombre', 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Dégénéré', 'Homme serpent', 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Tourment', 'Démon', 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('humain', 'PJ', 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Dormeur', 'Homme serpent - habitant', 40, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Golem', 'Créature synthétique', 60, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Démon Mineur', 'Démon', 60, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Dormeur', 'Homme serpent - 3/cités', 60, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Solar', NULL, 80, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Demi  géant', NULL, 80, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Héros', 'Conan', 80, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Liches', 'Morts vivants', 80, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Vampire', 'Morts vivants', 80, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Dormeur', 'Homme serpent 1/cité', 100, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Rodeur', 'Homme serpent', 100, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Pharaon noir', NULL, 150, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Démon Majeur', 'Démon', 150, NULL)");
        $this->addSql("INSERT INTO creature_puissance (nom, famille, xp, notes) VALUES ('Ombre majeure', 'Ombre', 150, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE frise_entree');
        $this->addSql('DROP TABLE frise_age');
        $this->addSql('DROP TABLE creature_puissance');
    }
}
