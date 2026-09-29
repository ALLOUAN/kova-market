#!/usr/bin/env bash
#
# Helpers shared by backup.sh and restore.sh.

# A value of the .env file, quotes removed: env_value <file> <KEY>
env_value() {
    local value
    value="$(grep -E "^$2=" "$1" | tail -n 1 | cut -d '=' -f 2- || true)"
    value="${value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"
    printf '%s' "$value"
}

# MySQL client options read from the .env, in a file only the owner can read (the password never appears in the
# process list): mysql_options_file <env file> <target>
mysql_options_file() {
    local connection
    connection="$(env_value "$1" DB_CONNECTION)"

    if [ "$connection" != "mysql" ] && [ "$connection" != "mariadb" ]; then
        echo "DB_CONNECTION=$connection : seules les bases MySQL / MariaDB sont prises en charge." >&2
        exit 1
    fi

    umask 077
    {
        echo "[client]"
        echo "host=$(env_value "$1" DB_HOST)"
        echo "port=$(env_value "$1" DB_PORT)"
        echo "user=$(env_value "$1" DB_USERNAME)"
        echo "password=$(env_value "$1" DB_PASSWORD)"
    } > "$2"
}
