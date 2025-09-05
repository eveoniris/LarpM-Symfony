#!/bin/sh
# This file must not be set as executable, otherwise the mysql docker image
# entrypoint script runs it in a subshell and the job will not stop.

set -ex

echo Running mysqldump on initial database

mysqldump --complete-insert --no-tablespaces \
    --user="${EXPORTER_REMOTE_MYSQL_USER}" \
    --password="${EXPORTER_REMOTE_MYSQL_PASSWORD}" \
    --host="${EXPORTER_REMOTE_MYSQL_HOST}" \
    "${EXPORTER_REMOTE_MYSQL_DATABASE}" \
    > /tmp/export.sql

if ! [ "${EXPORTER_OBFUSCATE_SECRET_DATA}" != 0 ]; then
    mv /tmp/export.sql "${EXPORTER_FILENAME}"
    echo "Database dumped into '${EXPORTER_FILENAME}'"
    exit 0
fi

echo Importing dump in temporary database...

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" \
    --database="${MYSQL_DATABASE}" < /tmp/export.sql

echo Obfuscating secret data

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" \
    --database="${MYSQL_DATABASE}" <<EOF
-- Replace sensitive fields with placeholder text
SET @LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas imperdiet lacinia enim, sed sollicitudin urna sagittis eu. Integer nisl massa, aliquet dapibus fermentum non.';
SET @LOREM_LIGHT = 'lorem ipsum';
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
UPDATE user SET roles = '["ROLE_USER"]', email = concat('email_', id, '@noreply.com'), password = '\$2y\$13\$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', pwd = '\$2y\$13\$EpRnVPQP6sj/JiCDoiyhxOpYRdWuchOxSw30446I9xiIIJfOAp8SO', salt ='5um2fz77pbkswo0osswocog4wswc0g', username = concat('user_', id) WHERE id > 0;

-- truncate unused or voluminous tables
UPDATE objet SET photo_id = NULL;
TRUNCATE TABLE photo;
TRUNCATE TABLE watching_user;
TRUNCATE TABLE post_view;
TRUNCATE TABLE post;
TRUNCATE TABLE message;
TRUNCATE TABLE messenger_message;
EOF

echo Running mysqldump on temporary database

mysqldump --complete-insert --no-tablespaces \
    --user="root" --password="${MYSQL_ROOT_PASSWORD}" \
    "${MYSQL_DATABASE}" \
    > "${EXPORTER_FILENAME}"

echo "Database dumped into '${EXPORTER_FILENAME}'"

exit 0
