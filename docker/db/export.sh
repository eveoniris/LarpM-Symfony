#!/bin/sh
# This file must not be set as executable, otherwise the mysql docker image
# entrypoint script runs it in a subshell and the job will not stop.

set -e

echo Running mysqldump on initial database

mysqldump --complete-insert --no-tablespaces \
    --user="${EXPORTER_REMOTE_MYSQL_USER}" \
    --password="${EXPORTER_REMOTE_MYSQL_PASSWORD}" \
    --host="${EXPORTER_REMOTE_MYSQL_HOST}" \
    "${EXPORTER_REMOTE_MYSQL_DATABASE}" \
    > /tmp/export.sql

if [ -z "${EXPORTER_OBFUSCATE_SQL_FILE}" ]; then
    echo "Missing EXPORTER_OBFUSCATE_SQL_FILE"
    exit 1
fi

echo Importing dump in temporary database...

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" \
    --database="${MYSQL_DATABASE}" < /tmp/export.sql

echo Obfuscating secret data

mysql --user=root --password="${MYSQL_ROOT_PASSWORD}" \
    --database="${MYSQL_DATABASE}" < "${EXPORTER_OBFUSCATE_SQL_FILE}"

echo Running mysqldump on temporary database

mysqldump --complete-insert --replace --no-tablespaces --no-data \
    --user="root" --password="${MYSQL_ROOT_PASSWORD}" \
    "${MYSQL_DATABASE}" \
    > "${EXPORTER_FILENAME_SCHEMA}"

mysqldump --complete-insert --no-tablespaces --no-create-info \
    --user="root" --password="${MYSQL_ROOT_PASSWORD}" \
    "${MYSQL_DATABASE}" \
    > "${EXPORTER_FILENAME_DATA}"

echo "Database dumped into '${EXPORTER_FILENAME_SCHEMA}' and '${EXPORTER_FILENAME_DATA}'"

exit 0
