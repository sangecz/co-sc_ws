#!/bin/bash

DEFAULT_SCRIPTS_DIR="scripts"

# check existence and readability of a folder where create_co-sc.sh is located
BASEDIR="$1"
[ ! -d "$BASEDIR" ] && exit 1
[ ! -r "$BASEDIR" ] && exit 1

# check writebility of a folder where scripts are located
SC_DIR="$BASEDIR/$DEFAULT_SCRIPTS_DIR"
[ ! -w "$SC_DIR" ] && exit 2

# create filename respecting BASEDIR and SC_DIR
f="$(echo "$2" | awk -F"/" '{ print $NF }' )"
filename="$SC_DIR/$f"

# sequence of commands to create executable script
# owner: editor
# group: co-sc
# GUID bit
# expected permissions: rwxr-x---
touch "$filename" || exit 2
chown editor:co-sc "$filename" || exit 3
chmod 4750 "$filename" || exit 4
chmod ug+x "$filename" || exit 5

# fill new script with its content
echo "$3" > $filename && { 
	echo "Script successfully created or edited."
	exit 0
}

