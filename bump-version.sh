#!/bin/bash

version=$1

if [[ -z "$version" ]]; then
    echo "Missing version" >&2
    exit 1
fi

date_version="$(date +"%Y%m%d00")"

while read -r path; do
    sed -i "s/\\(\\\$plugin->release\\s*=\\s*\\)'\\([0-9.]\\+\\)'/\1'$version'/" "$path"
    sed -i "s/\\(\\\$plugin->version\\s*=\\s*\\)\\([0-9]\\+\\)/\1$date_version/" "$path"
    sed -i "s/^\\(\\s\\+'[a-zA-Z0-9_]\\+'\\s*=>\\s*\\)\\([0-9]\\+\\)/\1$date_version/" "$path"
done < <(find plugins -type f -name 'version.php')
