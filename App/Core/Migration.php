<?php

namespace App;
$args = $argv;

var_dump(\App\DB::run('select * from ApiTokens'));
echo "\n";