<?php

namespace Swidly\Core\Commands;

class MigrationCommand extends AbstractCommand 
{
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
        
        // ... rest of model creation logic
    }
}