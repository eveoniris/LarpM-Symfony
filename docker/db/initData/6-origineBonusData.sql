-- Jeu de données de développement pour le bonus d'origine figé à la première participation.
--
-- Règle : à sa première participation, le PJ garde le bonus d'origine si le groupe POSSÈDE (fiefs, territoire.groupe_id)
-- le territoire d'origine du PJ. Sinon il reçoit la langue du groupe (groupe.territoire_id) et pas le bonus.
-- La décision est enregistrée dans personnage_bonus (source ORIGINE : ACTIVE = acquis, DELETED = refusé).
--
-- Script idempotent : il n'ajoute que ce qui manque et peut être rejoué à tout moment sur une base existante :
--   docker compose exec -T database mysql -uadmin -ppassword larpm < docker/db/initData/6-origineBonusData.sql
--
-- Il s'appuie sur le GN 7 et ses billets (4-newData.sql). Les colonnes personnage_bonus.source et territoire_id
-- proviennent de la migration Version20261019120000 : à l'initialisation du conteneur les migrations ne sont pas
-- encore jouées, le bloc concerné se saute alors de lui-même et il suffit de rejouer ce script après la migration.
-- Aucun écouteur Doctrine ne tourne ici : les décisions sont écrites telles que l'écouteur les aurait produites.
--
-- Comptes (mot de passe : azerty) et cas couverts, tous sur le GN 7, origine du PJ = Origine Test Alpha sauf E :
--   origine-a@test.com  A : groupe possédant l'origine                         -> bonus ACQUIS
--   origine-b@test.com  B : groupe sans fief commun, territoire de réf. autre   -> bonus REFUSÉ
--   origine-c@test.com  C : territoire de réf. du groupe = origine, aucun fief  -> bonus REFUSÉ (l'ancien calcul l'acceptait)
--   origine-d@test.com  D : participation sans groupe                           -> aucune décision (différée) ;
--                          affecter le PJ au groupe « Groupe Test Origine Fief » dans l'appli pour déclencher la décision
--   origine-e@test.com  E : fief perdu depuis l'acquisition (origine Beta)      -> bonus ACQUIS, conservé
--   origine-f@test.com  F : ancien PJ non repris (aucune décision, même groupe que C)
--                          -> ancien calcul : bonus actif ; cas « douteux » de la commande de reprise

-- ─────────────────────────────────────────────────────────────────────────────
-- Comptes
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.user (etat_civil_id, email, password, pwd, salt, rights, creation_date, username, isEnabled,
                        confirmationToken, timePasswordResetRequested, discr, trombineUrl, lastConnectionDate,
                        personnage_id, coeur, roles)
SELECT 1, v.email, '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
       '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
       '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER', '2019-10-30 20:56:18', v.username, 1, null, null, 'extended',
       null, null, null, null, '["ROLE_USER"]'
FROM (SELECT 'origine-a@test.com' AS email, 'origine-a' AS username
      UNION ALL SELECT 'origine-b@test.com', 'origine-b'
      UNION ALL SELECT 'origine-c@test.com', 'origine-c'
      UNION ALL SELECT 'origine-d@test.com', 'origine-d'
      UNION ALL SELECT 'origine-e@test.com', 'origine-e'
      UNION ALL SELECT 'origine-f@test.com', 'origine-f') v
WHERE NOT EXISTS (SELECT 1 FROM larpm.user u WHERE u.email = v.email);

-- ─────────────────────────────────────────────────────────────────────────────
-- Territoires d'origine et bonus d'origine (NATIVE)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.territoire (nom, discr, ordre_social)
SELECT v.nom, 'extended', 0
FROM (SELECT 'Origine Test Alpha' AS nom UNION ALL SELECT 'Origine Test Beta') v
WHERE NOT EXISTS (SELECT 1 FROM larpm.territoire t WHERE t.nom = v.nom);

INSERT INTO larpm.bonus (titre, description, type, valeur, periode, json_data, discr)
SELECT 'Bonus Origine Test', 'Bonus de renommée lié à l''origine (jeu de test du bonus d''origine figé).',
       'RENOMME', 1, 'NATIVE', '{"renomme": 1}', 'extended'
WHERE NOT EXISTS (SELECT 1 FROM larpm.bonus b WHERE b.titre = 'Bonus Origine Test');

INSERT INTO larpm.origine_bonus (bonus_id, territoire_id, status, creation_date)
SELECT b.id, t.id, 'ACTIVE', NOW()
FROM larpm.bonus b
         INNER JOIN larpm.territoire t ON t.nom IN ('Origine Test Alpha', 'Origine Test Beta')
WHERE b.titre = 'Bonus Origine Test'
  AND NOT EXISTS (SELECT 1 FROM larpm.origine_bonus ob WHERE ob.bonus_id = b.id AND ob.territoire_id = t.id);

-- ─────────────────────────────────────────────────────────────────────────────
-- Groupes. territoire_id = territoire de référence (langue) ; fiefs possédés = territoire.groupe_id (ci-dessous).
--   Fief    : territoire de réf. Beta, possédera Alpha
--   Autre   : territoire de réf. Alpha, possédera Beta (cas C et F : réf. = origine mais aucun fief commun)
--   Neutre  : territoire de réf. Beta, aucun fief
--   Perdu   : territoire de réf. Alpha, ne possède plus Beta (cas E)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.groupe (nom, numero, code, pj, territoire_id, discr, `lock`)
SELECT v.nom, v.numero, v.code, 1, (SELECT id FROM larpm.territoire WHERE nom = v.reference), 'extended', 0
FROM (SELECT 'Groupe Test Origine Fief' AS nom, 9961 AS numero, 'TESTORF' AS code, 'Origine Test Beta' AS reference
      UNION ALL SELECT 'Groupe Test Origine Autre', 9962, 'TESTORA', 'Origine Test Alpha'
      UNION ALL SELECT 'Groupe Test Origine Neutre', 9963, 'TESTORN', 'Origine Test Beta'
      UNION ALL SELECT 'Groupe Test Origine Perdu', 9964, 'TESTORP', 'Origine Test Alpha') v
WHERE NOT EXISTS (SELECT 1 FROM larpm.groupe g WHERE g.nom = v.nom);

UPDATE larpm.territoire t
    INNER JOIN larpm.groupe g ON g.nom = 'Groupe Test Origine Fief'
SET t.groupe_id = g.id
WHERE t.nom = 'Origine Test Alpha' AND t.groupe_id IS NULL;

UPDATE larpm.territoire t
    INNER JOIN larpm.groupe g ON g.nom = 'Groupe Test Origine Autre'
SET t.groupe_id = g.id
WHERE t.nom = 'Origine Test Beta' AND t.groupe_id IS NULL;

-- Sessions des groupes sur le GN 7.
INSERT INTO larpm.groupe_gn (groupe_id, gn_id, discr, free, code, place_available, agents, bateaux, sieges, initiative)
SELECT g.id, 7, 'extended', 1, g.code, 5, 0, 0, 0, 0
FROM larpm.groupe g
WHERE g.nom IN ('Groupe Test Origine Fief', 'Groupe Test Origine Autre', 'Groupe Test Origine Neutre',
                'Groupe Test Origine Perdu')
  AND NOT EXISTS (SELECT 1 FROM larpm.groupe_gn gg WHERE gg.groupe_id = g.id AND gg.gn_id = 7);

-- ─────────────────────────────────────────────────────────────────────────────
-- Personnages (tous d'origine Alpha, sauf E d'origine Beta)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.personnage (classe_id, age_id, genre_id, nom, vivant, discr, user_id, territoire_id)
SELECT 14, 1, 1, v.nom, 1, 'extended', u.id, t.id
FROM (SELECT 'origine-a@test.com' AS email, 'Origine A Fief commun' AS nom, 'Origine Test Alpha' AS origine
      UNION ALL SELECT 'origine-b@test.com', 'Origine B Sans fief commun', 'Origine Test Alpha'
      UNION ALL SELECT 'origine-c@test.com', 'Origine C Reference sans fief', 'Origine Test Alpha'
      UNION ALL SELECT 'origine-d@test.com', 'Origine D Sans groupe', 'Origine Test Alpha'
      UNION ALL SELECT 'origine-e@test.com', 'Origine E Fief perdu', 'Origine Test Beta'
      UNION ALL SELECT 'origine-f@test.com', 'Origine F Ancien non repris', 'Origine Test Alpha') v
         INNER JOIN larpm.user u ON u.email = v.email
         INNER JOIN larpm.territoire t ON t.nom = v.origine
WHERE NOT EXISTS (SELECT 1 FROM larpm.personnage p WHERE p.nom = v.nom);

-- ─────────────────────────────────────────────────────────────────────────────
-- Participations au GN 7 : billet PJ, personnage, groupe (D reste sans groupe)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO larpm.participant (gn_id, user_id, subscription_date, discr, billet_id, personnage_id, groupe_gn_id)
SELECT 7, u.id, '2025-01-01 12:00:00', 'extended',
       (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1),
       p.id,
       (SELECT gg.id FROM larpm.groupe_gn gg INNER JOIN larpm.groupe g ON g.id = gg.groupe_id
        WHERE gg.gn_id = 7 AND g.nom = v.groupe)
FROM (SELECT 'origine-a@test.com' AS email, 'Origine A Fief commun' AS personnage, 'Groupe Test Origine Fief' AS groupe
      UNION ALL SELECT 'origine-b@test.com', 'Origine B Sans fief commun', 'Groupe Test Origine Neutre'
      UNION ALL SELECT 'origine-c@test.com', 'Origine C Reference sans fief', 'Groupe Test Origine Autre'
      UNION ALL SELECT 'origine-d@test.com', 'Origine D Sans groupe', NULL
      UNION ALL SELECT 'origine-e@test.com', 'Origine E Fief perdu', 'Groupe Test Origine Perdu'
      UNION ALL SELECT 'origine-f@test.com', 'Origine F Ancien non repris', 'Groupe Test Origine Autre') v
         INNER JOIN larpm.user u ON u.email = v.email
         INNER JOIN larpm.personnage p ON p.nom = v.personnage
WHERE NOT EXISTS (SELECT 1 FROM larpm.participant pa WHERE pa.gn_id = 7 AND pa.user_id = u.id);

-- ─────────────────────────────────────────────────────────────────────────────
-- Décisions enregistrées (A, B, C, E), uniquement si la migration a été jouée. D et F n'en ont volontairement pas.
-- ─────────────────────────────────────────────────────────────────────────────
SET @colonneBonus = (SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = 'larpm' AND TABLE_NAME = 'personnage_bonus' AND COLUMN_NAME = 'source');

SET @sql = IF(@colonneBonus > 0,
    'INSERT INTO larpm.personnage_bonus (personnage_id, bonus_id, creation_date, status, source, territoire_id)
     SELECT p.id, b.id, NOW(), v.statut, ''ORIGINE'', p.territoire_id
     FROM (SELECT ''Origine A Fief commun'' AS nom, ''ACTIVE'' AS statut
           UNION ALL SELECT ''Origine B Sans fief commun'', ''DELETED''
           UNION ALL SELECT ''Origine C Reference sans fief'', ''DELETED''
           UNION ALL SELECT ''Origine E Fief perdu'', ''ACTIVE'') v
              INNER JOIN larpm.personnage p ON p.nom = v.nom
              INNER JOIN larpm.bonus b ON b.titre = ''Bonus Origine Test''
     WHERE NOT EXISTS (SELECT 1 FROM larpm.personnage_bonus pb
                       WHERE pb.personnage_id = p.id AND pb.bonus_id = b.id AND pb.territoire_id = p.territoire_id)',
    'SELECT ''Colonne personnage_bonus.source absente : rejouer ce script après les migrations.''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
