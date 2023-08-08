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

        if($mode === 'controller') {
            $this->makeController($name);
        } else if($mode === 'model') {
            $this->makeModel($name);
        } else if($mode === 'migration') {
            $this->makeMigration($name);
        } else if($mode === 'seeder') {
            $this->makeSeeder($name);
        } else if($mode === 'middleware') {
            $this->makeMiddleware($name);
        } else if($mode === 'command') {
            $this->makeCommand($name);
        } else if($mode === 'event') {
            $this->makeEvent($name);
        } else if($mode === 'listener') {
            $this->makeListener($name);
        } else if($mode === 'job') {
            $this->makeJob($name);
        } else if($mode === 'notification') {
            $this->makeNotification($name);
        } else if($mode === 'policy') {
            $this->makePolicy($name);
        } else if($mode === 'request') {
            $this->makeRequest($name);
        } else if($mode === 'resource') {
            $this->makeResource($name);
        } else if($mode === 'rule') {
            $this->makeRule($name);
        } else if($mode === 'test') {
            $this->makeTest($name);
        } else if($mode === 'all') {
            $this->makeController($name);
            $this->makeModel($name);
            $this->makeMigration($name);
            $this->makeSeeder($name);
            $this->makeMiddleware($name);
            $this->makeCommand($name);
            $this->makeEvent($name);
            $this->makeListener($name);
            $this->makeJob($name);
            $this->makeNotification($name);
            $this->makePolicy($name);
            $this->makeRequest($name);
            $this->makeResource($name);
            $this->makeRule($name);
            $this->makeTest($name);
        } else {
            echo "Invalid mode: $mode\n\r";
        }
    }

    private function makeController($name) {
        $name = ucfirst($name);
        $path = "App/Http/Controllers/$name.php";
        $content = "<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Swidly\Core\Controller;

class $name extends Controller {
    // 
}
";
        if(file_exists($path)) {
            echo "Controller already exists: $name\n\r";
        } else {
            file_put_contents($path, $content);
            echo "Controller created: $name\n\r";
        }
    }

    private function makeModel($name) {
        $name = ucfirst($name);
        $path = "App/Models/$name.php";
        $content = "<?php

declare(strict_types=1);

namespace App\Models;

use Swidly\Core\Model;

class $name extends Model {
    // 
}
";
        if(file_exists($path)) {
            echo "Model already exists: $name\n\r";
        } else {
            file_put_contents($path, $content);
            echo "Model created: $name\n\r";
        }
    }

    private function makeMigration($name) {
        $name = ucfirst($name);
        $path = "App/Migrations/$name.php";
        $content = "<?php

declare(strict_types=1);

namespace App\Migrations;

use Swidly\Core\Migration;

class $name extends Migration {
    // 
}
";
        if(file_exists($path)) {
            echo "Migration already exists: $name\n\r";
        } else {
            file_put_contents($path, $content);
            echo "Migration created: $name\n\r";
        }
    }

    private function makeSeeder($name) {
        $name = ucfirst($name);
        $path = "App/Seeders/$name.php";
        $content = "<?php

declare(strict_types=1);

namespace App\Seeders;

use Swidly\Core\Seeder;

class $name extends Seeder {
    // 
}
";
        if(file_exists($path)) {
            echo "Seeder already exists: $name\n\r";
        } else {
            file_put_contents($path, $content);
            echo "Seeder created: $name\n\r";
        }
    }

    private function makeMiddleware($name) {
        $name = ucfirst($name);
        $path = "App/Http/Middleware/$name.php";
        $content = "<?php";
    }
}
