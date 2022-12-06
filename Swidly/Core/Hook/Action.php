<?php

declare(strict_types=1);

namespace Swidly\Core\Hook;

use Swidly\Core\Enums\Priority;

class Action {
    public bool $isCompleted = false;

    public function __construct(
        private \callable|\Closure $callback,
        private Priority $priority,
        private bool $runOnce = false,
    )
    {
        $this->isCompleted = false;
    }

    public function getPriority(): Priority {
        return $this->priority;
    }

    public function getCallback(): \callable|\Closure {
        return $this->callback;
    }

    public function runCallback(...$arguments) {
        call_user_func_array($this->getCallback(), ...$arguments);
        $this->isCompleted = true;
    }

    public function isRunOnce(): bool {
        return $this->runOnce;
    }
}