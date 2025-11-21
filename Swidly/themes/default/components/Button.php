<?php

declare(strict_types=1);

namespace Swidly\themes\default\components;

use Swidly\Core\Component;

class Button extends Component
{
    /**
     * The button type.
     *
     * @var string
     */
    public string $type = 'primary' | 'secondary' | 'success' | 'danger' | 'default';

    /**
     * Create a new component instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->initializeProperties($attributes);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return string
     */
    public function render(): string
    {
        $class = match($this->type) {
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'success' => 'btn-success',
            'danger' => 'btn-danger',
            default => 'btn-default',
        };

        return sprintf(
            "<button class=\"btn rounded p-2 mb-1 text-sm %s\" %s> %s </button>",
            $class,
            $this->attributesToString(),
            $this->getAttribute('slot', ''),
        );
    }
}
