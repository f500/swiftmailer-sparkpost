#!/bin/sh

if [ -f build/logs/clover.xml ]; then
    wget https://scrutinizer-ci.com/ocular.phar
    php ocular.phar code-coverage:upload --format=php-clover build/logs/clover.xml
    echo "Uploaded coverage code"
fi
