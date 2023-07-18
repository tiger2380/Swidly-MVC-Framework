<?php

declare(strict_types=1);

namespace Swidly\Core;

class SwidlyCommand {
    private $version = '1.0.0';

    public function showhelp() {
echo "
Swidly v$this->version Command Line Tool

Database:
    db::create       Create a new database scheme
    db::seed         Runs the specified seeder to populate known data into database
    db::table        Retrieves information on the selected table
\n\r
";
    }

    public function make($mode, $name = null) {

    }
}
