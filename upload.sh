#!/bin/sh
rm -rf output/*
php bin/generate.php
minisign -S -m output/*.json

# rename .minisig to .sig and keep only the two first lines to make them the 
# same as signify
for I in $(ls output/*.minisig)
do 
    BN=$(basename ${I} .minisig)
    cat ${I} | head -2 > output/${BN}.sig
    rm ${I}
done

rsync -avzuh -e ssh output/ argon.tuxed.net:/var/www/html/fkooman/eduVPN/discovery/v2 --progress --exclude ".git"
