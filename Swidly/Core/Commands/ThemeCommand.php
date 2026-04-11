<?php

namespace Swidly\Core\Commands;

class ThemeCommand extends AbstractCommand 
{
    private const THEME_PHP_TEMPLATE = <<<'STR'
<?php

return [
  "name" => "%s",
  "version" => "1.0.0",
  "title" => "%s",
  "favicon" =>  "",
  "description" => "",
  "single_page" => false,
  "screenshot" => ""
];
STR;

    private const GITKEEP_CONTENT = '';

    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        
        if (empty($name)) {
            throw new \InvalidArgumentException("Theme name is required");
        }

        // Convert theme name to lowercase for directory name
        $themeDirName = strtolower($name);
        $themeBasePath = __DIR__ . '/../../themes/' . $themeDirName;
        
        if (file_exists($themeBasePath)) {
            throw new \RuntimeException("Theme already exists: $themeBasePath");
        }

        formatPrintLn(['cyan', 'bold'], "Creating theme: $name");
        
        // Create main theme directory
        mkdir($themeBasePath, 0755, true);
        formatPrintLn(['green'], "✓ Created theme directory: $themeBasePath");
        
        // Create subdirectories
        $directories = [
            'assets',
            'components',
            'controllers',
            'models',
            'views'
        ];
        
        foreach ($directories as $dir) {
            $dirPath = $themeBasePath . '/' . $dir;
            mkdir($dirPath, 0755, true);
            // Add .gitkeep to keep empty directories in git
            file_put_contents($dirPath . '/.gitkeep', self::GITKEEP_CONTENT);
            formatPrintLn(['green'], "✓ Created directory: $dir/");
        }
        
        // Create theme.php file
        $themePhpContent = sprintf(
            self::THEME_PHP_TEMPLATE,
            ucfirst($name),
            ucfirst($name)
        );
        
        file_put_contents($themeBasePath . '/theme.php', $themePhpContent);
        formatPrintLn(['green'], "✓ Created theme.php configuration file");
        
        formatPrintLn(['green', 'bold'], "\n✓ Theme '$name' created successfully!");
        formatPrintLn(['yellow'], "\nTheme structure:");
        formatPrintLn(['white'], "  Swidly/themes/$themeDirName/");
        formatPrintLn(['white'], "  ├── assets/");
        formatPrintLn(['white'], "  ├── components/");
        formatPrintLn(['white'], "  ├── controllers/");
        formatPrintLn(['white'], "  ├── models/");
        formatPrintLn(['white'], "  ├── views/");
        formatPrintLn(['white'], "  └── theme.php");
    }
}
