<?php

namespace Swidly;
$args = $argv;

var_dump(\Swidly\DB::run('select * from ApiTokens'));
echo "\n";