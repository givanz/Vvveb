#!/bin/bash

for f in public/themes/*
do
	if [ -e ./$f/gulpfile.js ]
	then
		echo $f
		npm i --prefix $f
		npm run gulp --prefix $f
	fi
done	

for f in public/admin/*
do
	if [ -e ./$f/gulpfile.js ]
	then
		echo $f
		npm i --prefix $f
		npm run gulp --prefix $f
	fi
done

rm -rf storage/compiled-templates/app_* storage/compiled-templates/admin_* storage/cache/* storage/logs/* storage/backup/* public/page-cache/* public/assets-cache/* public/image-cache/* public/themes/*/backup/* vvveb.zip 

zip -9 -X -r vvveb.zip ./ -x '*/node_modules/*' -x 'tests/*' -x 'test.php' -x 'phpunit.xml' -x '.git/*'  -x '/config/db.php'  -x '*/src/*' -x '*/scss/*' -x '*/resources/svg/*/*/*.svg' -x '/public/resources/*' 
