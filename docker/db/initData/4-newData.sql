-- new data for the new schema
INSERT INTO larpm.user (etat_civil_id, email, password, pwd, salt, rights, creation_date, username, isEnabled,
                        confirmationToken, timePasswordResetRequested, discr, trombineUrl, lastConnectionDate,
                        personnage_id, coeur, personnage_secondaire_id, roles)
VALUES (1, 'test@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', ',ROLE_ORGA,ROLE_ADMIN,ROLE_STOCK,ROLE_REGLE,ROLE_CARTOGRAPHE,ROLE_MODERATOR',
        '2019-10-30 20:56:18', 'test', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_ADMIN","ROLE_USER","ROLE_ORGA"]'),
       (2, 'user@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'test2', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_USER"]'),
       (1, 'scenariste@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_SCENARISTE',
        '2019-10-30 20:56:18', 'scenariste', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_SCENARISTE","ROLE_USER"]'),
       (1, 'wargame@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_WARGAME',
        '2019-10-30 20:56:18', 'wargame', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_WARGAME","ROLE_USER"]'),
       (1, 'chef@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'chef', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_USER"]'),
       (2, 'joueur@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'joueur', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_USER"]')
    ;

-- Fixtures pour tester le recrutement (candidatures / invitations) sur GN 7 (LH7, actif).
-- Les FK sont câblées par sous-requêtes car les id user/participant/groupe_gn sont auto-incrémentés.

-- Groupe de test dont chef@test.com est le responsable persistant.
INSERT INTO larpm.groupe (responsable_id, nom, numero, code, pj, discr, `lock`)
VALUES ((SELECT id FROM larpm.user WHERE email = 'chef@test.com'),
        'Groupe Test Recrutement', 9999, 'TESTREC', 1, 'extended', 0);

-- Participant du chef sur GN 7 (avec un billet PJ).
INSERT INTO larpm.participant (gn_id, user_id, subscription_date, discr, billet_id)
VALUES (7, (SELECT id FROM larpm.user WHERE email = 'chef@test.com'), '2025-01-01 12:00:00', 'extended',
        (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1));

-- Session (groupe_gn) du groupe de test sur GN 7 : ouverte au recrutement, chef = responsable.
INSERT INTO larpm.groupe_gn (groupe_id, gn_id, responsable_id, discr, free, code, place_available, agents, bateaux, sieges, initiative)
VALUES ((SELECT id FROM larpm.groupe WHERE nom = 'Groupe Test Recrutement'),
        7,
        (SELECT id FROM larpm.participant WHERE gn_id = 7 AND user_id = (SELECT id FROM larpm.user WHERE email = 'chef@test.com')),
        'extended', 1, 'TESTREC', 5, 0, 0, 0, 0);

-- Le chef est rattaché à sa propre session.
UPDATE larpm.participant
SET groupe_gn_id = (SELECT id FROM larpm.groupe_gn WHERE groupe_id = (SELECT id FROM larpm.groupe WHERE nom = 'Groupe Test Recrutement') AND gn_id = 7)
WHERE gn_id = 7 AND user_id = (SELECT id FROM larpm.user WHERE email = 'chef@test.com');

-- Joueur sans groupe mais avec un billet : peut postuler / être invité.
INSERT INTO larpm.participant (gn_id, user_id, subscription_date, discr, billet_id)
VALUES (7, (SELECT id FROM larpm.user WHERE email = 'joueur@test.com'), '2025-01-01 12:00:00', 'extended',
        (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1));

-- Fixtures pour tester manuellement la liste des personnages sélectionnables (GroupeGnType) sur GN 7 (LH7, actif).

INSERT INTO larpm.user (etat_civil_id, email, password, pwd, salt, rights, creation_date, username, isEnabled,
                        confirmationToken, timePasswordResetRequested, discr, trombineUrl, lastConnectionDate,
                        personnage_id, coeur, roles)
VALUES (1, 'capitaine@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'capitaine', 1, null, null, 'extended', null, null, null, null,
        '["ROLE_USER"]'),
       (1, 'lieutenant@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'lieutenant', 1, null, null, 'extended', null, null, null, null,
        '["ROLE_USER"]'),
       (1, 'rival@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'rival', 1, null, null, 'extended', null, null, null, null,
        '["ROLE_USER"]')
    ;

-- Deux groupes avec territoire (condition pour que GroupeGnType affiche les champs de titre).
INSERT INTO larpm.groupe (responsable_id, nom, numero, code, pj, territoire_id, discr, `lock`)
VALUES ((SELECT id FROM larpm.user WHERE email = 'capitaine@test.com'),
        'Groupe Test Titres', 9998, 'TESTTIT', 1, 1, 'extended', 0),
       ((SELECT id FROM larpm.user WHERE email = 'rival@test.com'),
        'Groupe Rival Titres', 9997, 'TESTTITR', 1, 1, 'extended', 0);

-- Personnages : capitaine possède 2 personnages vivants (Capitaine Un lié à sa participation, Capitaine Deux non lié).
INSERT INTO larpm.personnage (classe_id, age_id, genre_id, nom, vivant, discr, user_id)
VALUES (14, 1, 2, 'Capitaine Un', 1, 'extended', (SELECT id FROM larpm.user WHERE email = 'capitaine@test.com')),
       (14, 1, 2, 'Capitaine Deux', 1, 'extended', (SELECT id FROM larpm.user WHERE email = 'capitaine@test.com')),
       (14, 1, 2, 'Lieutenant Un', 1, 'extended', (SELECT id FROM larpm.user WHERE email = 'lieutenant@test.com')),
       (14, 1, 2, 'Rival Un', 1, 'extended', (SELECT id FROM larpm.user WHERE email = 'rival@test.com'));

-- Participations sur GN 7.
INSERT INTO larpm.participant (gn_id, user_id, subscription_date, discr, billet_id, personnage_id)
VALUES (7, (SELECT id FROM larpm.user WHERE email = 'capitaine@test.com'), '2025-01-01 12:00:00', 'extended',
        (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1),
        (SELECT id FROM larpm.personnage WHERE nom = 'Capitaine Un')),
       (7, (SELECT id FROM larpm.user WHERE email = 'lieutenant@test.com'), '2025-01-01 12:00:00', 'extended',
        (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1),
        (SELECT id FROM larpm.personnage WHERE nom = 'Lieutenant Un')),
       (7, (SELECT id FROM larpm.user WHERE email = 'rival@test.com'), '2025-01-01 12:00:00', 'extended',
        (SELECT id FROM larpm.billet WHERE gn_id = 7 AND label = 'Inscription PJ' LIMIT 1),
        (SELECT id FROM larpm.personnage WHERE nom = 'Rival Un'));

-- groupe_gn du groupe de test : capitaine est responsable (chef), pas de suzerain nommé (teste le fallback CHEF).
INSERT INTO larpm.groupe_gn (groupe_id, gn_id, responsable_id, discr, free, code, place_available, agents, bateaux, sieges, initiative)
VALUES ((SELECT id FROM larpm.groupe WHERE nom = 'Groupe Test Titres'),
        7,
        (SELECT id FROM larpm.participant WHERE gn_id = 7 AND user_id = (SELECT id FROM larpm.user WHERE email = 'capitaine@test.com')),
        'extended', 1, 'TESTTIT', 5, 0, 0, 0, 0);

-- groupe_gn rival : Rival Un y est déjà Connetable (pour tester l'exclusion "titre déjà attribué").
INSERT INTO larpm.groupe_gn (groupe_id, gn_id, responsable_id, connetable_id, discr, free, code, place_available, agents, bateaux, sieges, initiative)
VALUES ((SELECT id FROM larpm.groupe WHERE nom = 'Groupe Rival Titres'),
        7,
        (SELECT id FROM larpm.participant WHERE gn_id = 7 AND user_id = (SELECT id FROM larpm.user WHERE email = 'rival@test.com')),
        (SELECT id FROM larpm.personnage WHERE nom = 'Rival Un'),
        'extended', 1, 'TESTTITR', 5, 0, 0, 0, 0);

-- Rattachement des participants à leur groupe_gn respectif.
UPDATE larpm.participant
SET groupe_gn_id = (SELECT id FROM larpm.groupe_gn WHERE groupe_id = (SELECT id FROM larpm.groupe WHERE nom = 'Groupe Test Titres') AND gn_id = 7)
WHERE gn_id = 7 AND user_id IN (
    (SELECT id FROM larpm.user WHERE email = 'capitaine@test.com'),
    (SELECT id FROM larpm.user WHERE email = 'lieutenant@test.com')
);

UPDATE larpm.participant
SET groupe_gn_id = (SELECT id FROM larpm.groupe_gn WHERE groupe_id = (SELECT id FROM larpm.groupe WHERE nom = 'Groupe Rival Titres') AND gn_id = 7)
WHERE gn_id = 7 AND user_id = (SELECT id FROM larpm.user WHERE email = 'rival@test.com');
