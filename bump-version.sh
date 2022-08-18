#!/bin/bash

version=$1
suffix=$2

if [[ -z "$version" ]]; then
    echo "Missing version" >&2
    exit 1
fi

if [[ -z "$suffix" ]]; then
    suffix=00
elif ! grep -q '^[0-9][0-9]$' <<<"$suffix"; then
    echo "Invalid suffix: $suffix" >&2
    exit
fi

date_version="$(date +"%Y%m%d")$suffix"

while read -r path; do
    sed -i "s/\\(\\\$plugin->release\\s*=\\s*\\)'\\([-.0-9a-z]\\+\\)'/\1'$version'/" "$path"
    sed -i "s/\\(\\\$plugin->version\\s*=\\s*\\)\\([0-9]\\+\\)/\1$date_version/" "$path"
    sed -i "s/^\\(\\s\\+'[a-zA-Z0-9_]\\+'\\s*=>\\s*\\)\\([0-9]\\+\\)/\1$date_version/" "$path"
done < <(find plugins -type f -name 'version.php')
