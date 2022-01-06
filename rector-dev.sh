#!/bin/bash

cd /var/www/html/code-distortion/adapt-php7 || exit
rm -rf * .*
cp -pr /var/www/html/code-distortion/adapt/. /var/www/html/code-distortion/adapt-php7
rm -rf _config _src

git add . --all
git commit -m "WIP"

cp -pr config _config
cp -pr src _src

./vendor/bin/rector process _src _config
./vendor/bin/phpcbf _src _config
sed -i 's/: self//' _src/Boot/BootCommandInterface.php
sed -i 's/: self//' _src/Boot/BootCommandLaravel.php
sed -i 's/: self//' _src/Boot/BootRemoteBuildInterface.php
sed -i 's/: self//' _src/Boot/BootRemoteBuildLaravel.php
sed -i 's/: self//' _src/Boot/BootTestInterface.php
sed -i 's/: self//' _src/Boot/BootTestAbstract.php
sed -i 's/: self//' _src/Boot/BootTestLaravel.php
sed -i 's/public function boot(\$router)/public function boot(Router \$router)/' _src/AdaptLaravelServiceProvider.php

git checkout master-test

git merge latest-test --no-commit

# fix conflicts

rm -rf config src

mv _config config
mv _src src
