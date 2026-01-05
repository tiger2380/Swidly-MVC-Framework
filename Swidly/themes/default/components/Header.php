<?php

declare(strict_types=1);

namespace Swidly\themes\default\components;

use Swidly\Core\View;
use Swidly\Core\Component;

class Header extends Component
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
        $view = new View();
        $view->registerCommonComponents();
        $slot = $this->getAttribute('slot', '');

        return $view->render('inc/header', [
            'slot' => $slot,
            'attributes' => $this->attributes,
        ]);
    }
}