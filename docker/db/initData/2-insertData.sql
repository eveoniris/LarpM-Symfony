/*
To Anonymise
set @LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas imperdiet lacinia enim, sed sollicitudin urna sagittis eu. Integer nisl massa, aliquet dapibus fermentum non, efficitur sit amet justo. Maecenas et ligula sed dolor vulputate convallis at ut erat. Praesent eleifend massa sapien, eu blandit est facilisis semper. Suspendisse vitae lorem feugiat, lobortis sapien eu, dapibus ante. Donec laoreet dolor non lectus dictum pulvinar. Vestibulum in ultrices odio. Aliquam sem nisl, commodo ut ullamcorper ut, vulputate dictum libero. Curabitur dapibus imperdiet rutrum. In ligula ligula, laoreet placerat euismod quis, porta sit amet tortor.';
set @LOREM_LIGHT = 'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.';

UPDATE background SET text = @LOREM, titre =  concat('Titre_', id) WHERE id > 0;
UPDATE classe SET description = @LOREM, label_feminin = concat('Classe_f_', id), label_masculin = concat('Classe_m_', id) WHERE id > 0;
UPDATE competence SET description = @LOREM, materiel =  @LOREM_LIGHT, documentUrl = null WHERE id > 0;
UPDATE connaissance SET label =  concat('Label_', id), description = @LOREM, contraintes = @LOREM_LIGHT, documentUrl = null WHERE id > 0;
UPDATE construction SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE debriefing SET titre =  concat('Titre_', id), text = @LOREM WHERE id > 0;
UPDATE document SET titre =  concat('Titre_', id),  auteur = concat('Auteur_', id),  statut = concat('Statut_', id), documentUrl = null, description = @LOREM WHERE id > 0;
UPDATE etat_civil SET nom =  concat('Nom_', id),  prenom = concat('Prenom_', id),  prenom_usage = concat('PrenomUsage_', id), telephone = '0601010101', photo = null, personne_a_prevenir = 'lorem ipsum', tel_pap = '0601010101' WHERE id > 0;
UPDATE evenement SET text = @LOREM WHERE id > 0;
UPDATE experience_gain SET explanation = @LOREM WHERE id > 0;
UPDATE groupe SET nom = @LOREM_LIGHT, description = @LOREM WHERE id > 0;
UPDATE groupe_allie SET message = @LOREM, message_allie = @LOREM WHERE id > 0;
UPDATE groupe_enemy SET message = @LOREM WHERE id > 0;
UPDATE groupe_gn SET code =  concat('Code_', id) WHERE id > 0;
UPDATE heroisme_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE ingredient SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE intrigue SET titre =  concat('Titre_', id), description = @LOREM, text = @LOREM_LIGHT, resolution = @LOREM_LIGHT WHERE id > 0;
UPDATE item SET label =  concat('Label_', id), description = @LOREM, special = @LOREM_LIGHT WHERE id > 0;
UPDATE item_bak SET label =  concat('Label_', id), description = @LOREM, special = @LOREM_LIGHT WHERE id > 0;
UPDATE langue SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE lieu SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE lignees SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE localisation SET label =  concat('Localisation_', id), `precision` = @LOREM WHERE id > 0;
UPDATE loi SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
UPDATE message SET title =  concat('Titre_', id), text = @LOREM WHERE id > 0;
UPDATE notification SET text =  concat('Texte_', id) WHERE id > 0;
UPDATE objectif SET text =  concat('Texte_', id) WHERE id > 0;
UPDATE objet SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE lieu SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE photo SET name =  concat('Name_', id), real_name =  concat('RealName_', id), filename =  concat('filename', id) WHERE id > 0;
UPDATE post SET title =  concat('Title_', id), text = @LOREM WHERE id > 0;
UPDATE postulant SET explanation = @LOREM WHERE id > 0;
UPDATE potion SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
UPDATE priere SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
UPDATE proprietaire SET nom =  concat('Nom_', id), adresse = @LOREM_LIGHT, mail = 'noreply@noreply.com', tel = null WHERE id > 0;
UPDATE pugilat_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE renomme_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE topic SET title = concat('Title_', id), description = @LOREM WHERE id > 0;
UPDATE `user` SET roles = '["ROLE_USER"]' where id > 0;
UPDATE `user` SET email = concat('email_', id, '@noreply.com'), password = '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', pwd = '$2y$13$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', salt ='5um2fz77pbkswo0osswocog4wswc0g', username = concat('user_', id) WHERE id > 0;

*/
-- Will fill the database with the data in this will on first start with old larp data
