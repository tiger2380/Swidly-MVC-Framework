<?php

class PreInstallationChecker {
    private $requirements = [];
    private $errors = [];
    
    public function __construct() {
        $this->requirements = [
            'php_version' => '7.4.0',
            'extensions' => [
                'pdo',
                'pdo_mysql',
                'curl',
                'json',
                'session',
                'mbstring',
                'zip',
                'gd'
            ],
            'writable_paths' => [
                'temp/',
                'uploads/',
                'cache/'
            ]
        ];
    }

    public function checkAll() {
        $results = [
            'php_version' => $this->checkPHPVersion(),
            'extensions' => $this->checkExtensions(),
            'permissions' => $this->checkWritableDirectories(),
            'env' => $this->checkEnvironment(),
            'errors' => $this->errors
        ];
        
        return $results;
    }

    private function checkPHPVersion() {
        $current_version = phpversion();
        return [
            'required' => $this->requirements['php_version'],
            'current' => $current_version,
            'status' => version_compare($current_version, $this->requirements['php_version'], '>=')
        ];
    }

    private function checkExtensions() {
        $results = [];
        foreach ($this->requirements['extensions'] as $extension) {
            $results[$extension] = [
                'status' => extension_loaded($extension),
                'current' => extension_loaded($extension) ? 'Installed' : 'Not Installed'
            ];
        }
        return $results;
    }

    private function checkWritableDirectories() {
        $results = [];
        foreach ($this->requirements['writable_paths'] as $path) {
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $results[$path] = [
                'status' => is_writable($path),
                'current' => is_writable($path) ? 'Writable' : 'Not Writable'
            ];
        }
        return $results;
    }

    private function checkEnvironment() {
        return [
            'max_execution_time' => [
                'status' => ini_get('max_execution_time') >= 30,
                'current' => ini_get('max_execution_time'),
                'required' => '30'
            ],
            'memory_limit' => [
                'status' => $this->convertToBytes(ini_get('memory_limit')) >= $this->convertToBytes('128M'),
                'current' => ini_get('memory_limit'),
                'required' => '128M'
            ],
            'upload_max_filesize' => [
                'status' => $this->convertToBytes(ini_get('upload_max_filesize')) >= $this->convertToBytes('8M'),
                'current' => ini_get('upload_max_filesize'),
                'required' => '8M'
            ]
        ];
    }

    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int)$value;
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }

    public function canProceed() {
        $checks = $this->checkAll();
        return empty($checks['errors']) && 
               $checks['php_version']['status'] && 
               !in_array(false, array_column($checks['extensions'], 'status')) &&
               !in_array(false, array_column($checks['permissions'], 'status')) &&
               !in_array(false, array_column($checks['env'], 'status'));
    }
}

// Usage in install.php