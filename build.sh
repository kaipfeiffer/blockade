#!/bin/bash
version=$(cat blockade.php | grep "* Version" | grep -E -o -i '[\.0-9]{5,}$')
echo Plugin komprimieren...
cd ..
zip -r blockade.$version.zip blockade  -x .gitignore -x '*.git*'  -x '*composer.json*' -x '*composer.lock*' -x '*.DS_Store*' -x './app/*' -x './build.sh' -x  '*/data/logs/*.csv' -x  '*/data/logs/*.zip'  -x  '*/data/documentation/*'  -x  '*/data/zip/*' 
# cp blockade.$version.zip ./blockade/admin/data/zip/blockade.zip
echo Zipfile blockade.$version.zip erstellt 