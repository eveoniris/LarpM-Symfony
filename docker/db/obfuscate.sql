-- Replace sensitive fields with placeholder text
SET @LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas imperdiet lacinia enim, sed sollicitudin urna sagittis eu. Integer nisl massa, aliquet dapibus fermentum non.';
SET @LOREM_LIGHT = 'lorem ipsum';
SET FOREIGN_KEY_CHECKS=0;

-- truncate unused or voluminous tables
UPDATE objet SET photo_id = NULL;
DELETE FROM annonce;
DELETE FROM photo;
DELETE FROM watching_user;
DELETE FROM log_action;
DELETE FROM post_view;
DELETE FROM post;
DELETE FROM message;
DELETE FROM messenger_messages;

ALTER TABLE connaissance CHANGE documentUrl documentUrl VARCHAR(255) DEFAULT NULL;

-- update sensitive fields with placeholder text
UPDATE background SET text = @LOREM, titre =  concat('Titre_', id) WHERE id > 0;
UPDATE bonus SET description = @LOREM, titre =  concat('Titre_', id) WHERE id > 0;
UPDATE classe SET description = @LOREM, label_feminin = concat('Classe_f_', id), label_masculin = concat('Classe_m_', id), image_m = null, image_f = null  WHERE id > 0;
UPDATE competence SET description = @LOREM, materiel =  @LOREM_LIGHT, documentUrl = null WHERE id > 0;
UPDATE connaissance SET label =  concat('Label_', id), description = @LOREM, contraintes = @LOREM_LIGHT, documentUrl = null WHERE id > 0;
UPDATE construction SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE debriefing SET titre =  concat('Titre_', id), text = @LOREM WHERE id > 0;
UPDATE document SET titre =  concat('Titre_', id),  auteur = concat('Auteur_', id),  statut = concat('Statut_', id), documentUrl = concat(id, '.pdf'), description = @LOREM WHERE id > 0;
UPDATE espece SET nom =  concat('Nom_', id),  description =  @LOREM,  description_secrete =  @LOREM WHERE id > 0;
UPDATE etat_civil SET nom =  concat('Nom_', id),  prenom = concat('Prenom_', id),  prenom_usage = concat('PrenomUsage_', id), telephone = '0601010101', photo = null, personne_a_prevenir = 'lorem ipsum', tel_pap = '0601010101', probleme_medicaux = null, date_naissance = null, fedegn = null WHERE id > 0;
UPDATE evenement SET text = @LOREM WHERE id > 0;
UPDATE experience_gain SET explanation = @LOREM_LIGHT WHERE id > 0;
UPDATE groupe SET nom = @LOREM_LIGHT, description = @LOREM, code = null, materiel = null, description_membres = null, discord = null WHERE id > 0;
UPDATE groupe_allie SET message = @LOREM, message_allie = @LOREM WHERE id > 0;
UPDATE groupe_enemy SET message = @LOREM WHERE id > 0;
UPDATE groupe_gn SET code =  concat('Code_', id) WHERE id > 0;
UPDATE heroisme_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE ingredient SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE intrigue SET titre =  concat('Titre_', id), description = @LOREM, text = @LOREM_LIGHT, resolution = @LOREM_LIGHT WHERE id > 0;
UPDATE item SET label =  concat('Label_', id), description = @LOREM, special = @LOREM_LIGHT, description_secrete = @LOREM, description_scenariste = @LOREM WHERE id > 0;
UPDATE item_bak SET label =  concat('Label_', id), description = @LOREM, special = @LOREM_LIGHT WHERE id > 0;
UPDATE langue SET label =  concat('Label_', id), description = @LOREM WHERE id > 0;
UPDATE lieu SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE lignees SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
UPDATE localisation SET label =  concat('Localisation_', id), `precision` = @LOREM WHERE id > 0;
UPDATE loi SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
-- UPDATE message SET title =  concat('Titre_', id), text = @LOREM WHERE id > 0;
UPDATE merveille SET nom =  concat('Nom_', id), description = @LORM, description_scenariste = @LOREM, description_cartographe = @LOREM WHERE id > 0;
UPDATE notification SET text =  concat('Texte_', id) WHERE id > 0;
UPDATE objectif SET text =  concat('Texte_', id) WHERE id > 0;
UPDATE objet SET nom =  concat('Nom_', id), description = @LOREM WHERE id > 0;
-- UPDATE photo SET name =  concat('Name_', id), real_name =  concat('RealName_', id), filename = concat('filename', id) WHERE id > 0;
UPDATE personnage SET nom = concat('Nom_', id), surnom = nom = concat('Surnom_', id) WHERE id > 0;
UPDATE postulant SET explanation = @LOREM WHERE id > 0;
UPDATE potion SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
UPDATE priere SET label =  concat('Label_', id), description = @LOREM, documentUrl = null WHERE id > 0;
UPDATE proprietaire SET nom =  concat('Nom_', id), adresse = @LOREM_LIGHT, mail = 'noreply@noreply.com', tel = null WHERE id > 0;
UPDATE pugilat_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE religion SET description_orga = @LOREM_LIGHT WHERE id > 0;
UPDATE religion SET description = @LOREM_LIGHT, description_fervent = @LOREM_LIGHT, description_fanatique = @LOREM_LIGHT, description_pratiquant = @LOREM_LIGHT WHERE secret = 1;
UPDATE renomme_history SET explication = @LOREM_LIGHT WHERE id > 0;
UPDATE sort SET label = concat('Nom_', id), description = @LOREM, documentUrl = null WHERE secret = 1;
UPDATE secondary_group SET description_secrete = @LOREM, discord = null, materiel = null WHERE id > 0;
UPDATE secondary_group SET description = @LOREM WHERE secret = 1;
UPDATE technologie SET label = concat('Label_', id), description = @LOREM, documentURL = NULL WHERE id > 0;
UPDATE territoire SET nom = concat('Nom_', id), description_secrete = null WHERE id > 0;
UPDATE topic SET title = concat('Title_', id), description = @LOREM WHERE id > 0;
UPDATE user SET roles = '["ROLE_USER"]', email = concat('email_', id, '@noreply.com'), password = '\$2y\$13\$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', pwd = '\$2y\$13\$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', salt ='5um2fz77pbkswo0osswocog4wswc0g', username = concat('user_', id) WHERE id > 0;


