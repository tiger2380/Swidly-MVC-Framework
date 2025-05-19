<?php

namespace Swidly\Core\Commands;

class ModelCommand extends AbstractCommand 
{
        private const MODEL_TEMPLATE = <<<'STR'
<?php
namespace Swidly\themes\%s\models;
use Swidly\Core\Attributes\Column;
use Swidly\Core\Attributes\Table;
use Swidly\Core\Enums\Types;
use Swidly\Core\Model;

#[Table(name: '%s')]
class %sModel extends Model {
    #[Column(type: Types::INTEGER, isPrimary: true)]
    public int $id;
}
STR;

    public function execute(): void 
    {
        $name = $this->options['name'] ?? '';
        $theme = $this->options['theme'] ?? [];
        
        if (empty($name)) {
            throw new \InvalidArgumentException("Model name is required");
        }

        $modelPath = sprintf(
            $theme['base'].'/models/%sModel.php',
            ucfirst($name)
        );
        
        if (file_exists($modelPath)) {
            throw new \RuntimeException("Model already exists: $modelPath");
        }

        formatPrintLn(['cyan', 'bold'], "Creating model...");
        $content = sprintf(self::MODEL_TEMPLATE, $theme['name'], $name, ucfirst($name));
        file_put_contents($modelPath, $content);
    }
}