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

    public function setRunOnce(bool $runOnce): void {
        $this->runOnce = $runOnce;
    }

    public function setPriority(Priority $priority): void {
        $this->priority = $priority;
    }

    public function setCallback(\callable|\Closure $callback): void {
        $this->callback = $callback;
    }

    public function setCompleted(bool $isCompleted): void {
        $this->isCompleted = $isCompleted;
    }

    public function __toString(): string {
        return sprintf(
            'Action: %s, Priority: %s, RunOnce: %s, Completed: %s',
            $this->getCallback(),
            $this->getPriority(),
            $this->isRunOnce(),
            $this->isCompleted
        );
    }

    public function __debugInfo(): array {
        return [
            'callback' => $this->getCallback(),
            'priority' => $this->getPriority(),
            'runOnce' => $this->isRunOnce(),
            'completed' => $this->isCompleted
        ];
    }
}