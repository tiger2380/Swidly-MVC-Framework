<?php

namespace Swidly\Core\Commands;

abstract class AbstractCommand 
{
    protected array $options;
    
    public function __construct(array $options) 
    {
        $this->options = $options;
    }
    
    abstract public function execute(): void;

    public function getOptions(): array 
    {
        return $this->options;
    }
    
    public function setOptions(array $options): void 
    {
        $this->options = $options;
    }
}