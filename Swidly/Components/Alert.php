<?php

declare(strict_types=1);

namespace Swidly\Components;

use Swidly\Core\Component;

class Alert extends Component
{
    /**
     * The alert type.
     *
     * @var string
     */
    public string $type = 'info';

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
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info',
        };

        return sprintf(
            "<div class=\"rounded-lg p-4 mb-4 text-sm %s\" %s> %s </div>",
            $class,
            $this->attributesToString(),
            $this->getAttribute('slot', ''),
        );
    }
}
