<?php

declare(strict_types=1);

namespace App\Core\Hook;

use App\Core\Enums\Priority;

class Hook {
    private static array $hooks = [];

    public function __construct(
        public string $name,
        public bool $allowMulitpleCalls = true,
    )
    {
        if (!isset(self::$hooks[$name])) {
            self::$hooks[$name] = [
                'actions' => [],
                'done' => false
            ];
        }

        return $this;
    }

    public function addAction(\callable|\Closure $callback, Priority $priority, bool $runOnce = false): void {
        $this->getActions()[] = new Action($callback, $priority, $runOnce);

        $this->sortActions();
    }

    public function doCallback(): void {
        if (!$this->isDone() || $this->allowMulitpleCalls) {
            $actions = &$this->getActions();
            $args = func_get_args();

            foreach ($actions as $action) {
                if ($action->isRunOnce() && $action->isCompleted) {
                    continue;
                }

                $action->runCallback($args);
            }

            $this->setDone();
        }
    }

    public static function &getHook(string $name): bool|array {
        return array_key_exists($name, self::$hooks) ? self::$hooks[$name] : FALSE;
    }

    public function &getActions(): array {
        return self::$hooks[$this->name]['actions'];
    }

    public function setDone(): void {
        self::$hooks[$this->name]['done'] = true;
    }

    public function isDone(): bool {
        return self::$hooks[$this->name]['done'] === true;
    }

    public function sortActions(): void {
        usort($this->getActions(), fn ($a, $b) => $a->getPriority() - $b->getPriority());
    }
}