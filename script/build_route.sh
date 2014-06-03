#!/bin/bash

serverdir=/home/web/www.houstonbbs.com/server

tmp_file=/tmp/route.php

function error_exit
{
    ## do clean up
    rm -rf $tmp_file

    ## print error message
    echo "Error: $1"
    exit 1;
} 1>&2

rm -rf $tmp_file || error_exit "failed to initialize $tmp_file"

cat > $tmp_file <<'EOF'
<?php
// do not edit, generated by script/build_route.sh

$route = [
   'home' => 'site\\controller\\Home',
EOF

for i in $serverdir/controller/*.php; do
    ctrler=$(basename $i .php)
    uri="$(echo $ctrler | tr '[A-Z]' '[a-z]')"

    echo '   '\'$uri\'' => '\''site\\controller\\'$ctrler\'',' >> $tmp_file
done

cat >> $tmp_file <<'EOF'
];

//__END_OF_FILE__
EOF

if [[ ! -z "$(diff $tmp_file $serverdir/route.php)" ]]; then
    mv -f $serverdir/route.php $serverdir/route.php.backup && mv -f $tmp_file $serverdir/route.php
else
    echo "route.php file not changed, skip updating"
    rm -rf $tmp_file
fi
