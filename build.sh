#!/bin/sh

_output_dir="$(pwd)/dist"
if [ -d "$_output_dir" ]; then
    rm -rf "$_output_dir"
fi

mkdir "$_output_dir"

find ./plugins -maxdepth 1 -mindepth 1 -type d | while read -r _dir; do
    _zip_name="$(basename "$_dir").zip"
    _old_pwd="$(pwd)"
    cd "$_dir" || exit 1
    zip -r "$_output_dir/$_zip_name" 'warpwire' || exit 1
    cd "$_old_pwd" || exit 1
done
