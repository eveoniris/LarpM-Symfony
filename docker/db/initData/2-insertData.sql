-- Will fill the database with the data in this will on first start with old larp data (to anonimize)
INSERT INTO larpm.user (etat_civil_id, email, password, salt, rights, creation_date, username, isEnabled,
                        confirmationToken, timePasswordResetRequested, discr, trombineUrl, lastConnectionDate,
                        personnage_id, coeur, personnage_secondaire_id, roles, is_enabled)
VALUES (1, 'test@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        'salt12345', ',ROLE_ORGA,ROLE_ADMIN,ROLE_STOCK,ROLE_REGLE,ROLE_CARTOGRAPHE,ROLE_MODERATOR',
        '2019-10-30 20:56:18', 'test', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_ADMIN","ROLE_USER","ROLE_ORGA"]', null);
