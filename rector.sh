#!/bin/bash

if ! command -v php &> /dev/null; then
  echo
  echo 'ERROR: Please run in an environment where PHP is available'
  echo
  exit
fi



rm -rf _config _src

cp -pr config _config
cp -pr src _src

./vendor/bin/rector process --clear-cache _src _config
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

#git add src --all
#git add tests --all
#git commit -m "Merge branch 'latest'"
