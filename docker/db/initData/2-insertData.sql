/*
To AnonymiseDATE message SET title =  concat('Titre_', id), text = @LOREM WHERE id > 0;
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
