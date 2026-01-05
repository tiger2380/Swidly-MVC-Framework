<?php

declare(strict_types=1);

namespace Swidly\themes\default\components;

use Swidly\Core\Component;

class Footer extends Component
{
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
     * Render the component.
     *
     * @return string
     */
    public function render(): string
    {
        $slot = $this->getAttribute('slot', '');

        return <<<HTML
        <div {$this->attributes}>{$slot}</div>
        HTML;
    }
}