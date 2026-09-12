-- Jeu de données de développement pour la chaîne de personnages d'une participation :
-- principal -> substitution (si l'opus l'active) -> relève -> archétype de secours.
--
-- Ce script est idempotent : il n'ajoute que ce qui manque et peut être rejoué à tout
-- moment sur une base existante, en plus de l'initialisation du conteneur :
--   docker compose exec -T database mysql -uadmin -ppassword larpm < docker/db/initData/5-chainePersonnagesData.sql
--
-- Il s'appuie sur les comptes et le GN 7 créés par 4-newData.sql.
--
-- Les colonnes substitution_active (gn) et personnage_substitution_id (participant)
-- proviennent de la migration Version20260904120000. À l'initialisation du conteneur
-- les migrations ne sont pas encore jouées : les blocs concernés se sautent alors
-- d'eux-mêmes et il suffit de rejouer ce script après la migration.

-- ─────────────────────────────────────────────────────────────────────────────
-- Personnages supplémentaires du capitaine.
-- Genres volontairement différents : ils permettent de vérifier que l'archétype de
-- secours s'accorde bien au genre du personnage principal.
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.personnage (classe_id, age_id, genre_id, nom, vivant, discr, user_id)
SELECT 14, 1, 2, 'Capitaine Releve', 1, 'extended', u.id
FROM larpm.user u
WHERE u.email = 'capitaine@test.com'
  AND NOT EXISTS (SELECT 1 FROM larpm.personnage p WHERE p.nom = 'Capitaine Releve');

INSERT INTO larpm.personnage (classe_id, age_id, genre_id, nom, vivant, discr, user_id)
SELECT 14, 1, 1, 'Capitaine Substitution', 1, 'extended', u.id
FROM larpm.user u
WHERE u.email = 'capitaine@test.com'
  AND NOT EXISTS (SELECT 1 FROM larpm.personnage p WHERE p.nom = 'Capitaine Substitution');

-- Personnage mort : vérifie qu'il n'est jamais proposé, ni comme relève ou
-- substitution, ni comme personnage actif sur LarpManager.
INSERT INTO larpm.personnage (classe_id, age_id, genre_id, nom, vivant, discr, user_id)
SELECT 14, 1, 2, 'Capitaine Trepassee', 0, 'extended', u.id
FROM larpm.user u
WHERE u.email = 'capitaine@test.com'
  AND NOT EXISTS (SELECT 1 FROM larpm.personnage p WHERE p.nom = 'Capitaine Trepassee');

-- ─────────────────────────────────────────────────────────────────────────────
-- Relève et archétype de secours sur la participation du capitaine au GN 7.
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE larpm.participant p
    INNER JOIN larpm.user u ON u.id = p.user_id
    INNER JOIN larpm.personnage releve ON releve.nom = 'Capitaine Releve'
SET p.personnage_releve_id = releve.id
WHERE p.gn_id = 7
  AND u.email = 'capitaine@test.com'
  AND p.personnage_releve_id IS NULL;

UPDATE larpm.participant p
    INNER JOIN larpm.user u ON u.id = p.user_id
SET p.personnage_secondaire_id = (SELECT MIN(ps.id) FROM larpm.personnage_secondaire ps)
WHERE p.gn_id = 7
  AND u.email = 'capitaine@test.com'
  AND p.personnage_secondaire_id IS NULL
  AND EXISTS (SELECT 1 FROM larpm.personnage_secondaire);

-- ─────────────────────────────────────────────────────────────────────────────
-- Option de substitution du GN 7, uniquement si la migration a été jouée.
-- ─────────────────────────────────────────────────────────────────────────────
SET @colonneGn = (SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = 'larpm' AND TABLE_NAME = 'gn' AND COLUMN_NAME = 'substitution_active');

SET @sql = IF(@colonneGn > 0,
    'UPDATE larpm.gn SET substitution_active = 1,
        substitution_description = ''<p>Le jeu se déroule sur une île à explorer. Les personnages nobles ou politiques restés au pays y font avancer leurs intrigues lors des instances hors temps et hors lieu.</p>''
     WHERE id = 7',
    'SELECT ''Colonne gn.substitution_active absente : rejouer ce script après les migrations.''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- Personnage de substitution du capitaine, uniquement si la migration a été jouée.
-- ─────────────────────────────────────────────────────────────────────────────
SET @colonneParticipant = (SELECT COUNT(*) FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = 'larpm' AND TABLE_NAME = 'participant' AND COLUMN_NAME = 'personnage_substitution_id');

SET @sql = IF(@colonneParticipant > 0,
    'UPDATE larpm.participant p
        INNER JOIN larpm.user u ON u.id = p.user_id
        INNER JOIN larpm.personnage substitution ON substitution.nom = ''Capitaine Substitution''
     SET p.personnage_substitution_id = substitution.id
     WHERE p.gn_id = 7 AND u.email = ''capitaine@test.com'' AND p.personnage_substitution_id IS NULL',
    'SELECT ''Colonne participant.personnage_substitution_id absente : rejouer ce script après les migrations.''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────────────────────────────
-- Le lieutenant reste volontairement sans relève ni substitution : il sert de
-- contre-exemple pour vérifier l'affichage « Non choisi » et les invitations à choisir.
-- ─────────────────────────────────────────────────────────────────────────────
