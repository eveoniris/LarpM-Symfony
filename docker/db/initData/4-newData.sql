-- new data for the new schema
INSERT INTO larpm.user (etat_civil_id, email, password, salt, rights, creation_date, username, isEnabled,
                        confirmationToken, timePasswordResetRequested, discr, trombineUrl, lastConnectionDate,
                        personnage_id, coeur, personnage_secondaire_id, roles, is_enabled)
VALUES (1, 'test@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', ',ROLE_ORGA,ROLE_ADMIN,ROLE_STOCK,ROLE_REGLE,ROLE_CARTOGRAPHE,ROLE_MODERATOR',
        '2019-10-30 20:56:18', 'test', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_ADMIN","ROLE_USER","ROLE_ORGA"]', 1),
       (2, 'user@test.com', '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO',
        '5um2fz77pbkswo0osswocog4wswc0g', 'ROLE_USER',
        '2019-10-30 20:56:18', 'test', 1, null, null, 'extended', null, null, null, null, null,
        '["ROLE_USER"]', 1)
    ;
