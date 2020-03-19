#!/bin/sh
rm -rf output/*
php bin/generate.php
minisign -S -m output/*.json
rsync -avzuh -e ssh output/ argon.tuxed.net:/var/www/html/fkooman/eduVPN/discovery/v2 --progress --exclude ".git"
