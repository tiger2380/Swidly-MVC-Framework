<?php

namespace Swidly\Core;

class FlatDB
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    protected function path(string $collection): string
    {
        return $this->basePath . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $collection) . '.json';
    }

    protected function load(string $collection): array
    {
        $file = $this->path($collection);
        if (!file_exists($file)) return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }

    protected function save(string $collection, array $docs): void
    {
        file_put_contents(
            $this->path($collection),
            json_encode(array_values($docs), JSON_PRETTY_PRINT)
        );
    }

    protected function id(): string
    {
        return bin2hex(random_bytes(12));
    }

    public function insert(string $collection, array $doc): array
    {
        $docs = $this->load($collection);
        $doc['_id'] = $doc['_id'] ?? $this->id();
        $docs[] = $doc;
        $this->save($collection, $docs);
        return $doc;
    }

    // -----------------------------
    // Query Engine
    // -----------------------------

    protected function getValue(array $doc, string $path)
    {
        if (!str_contains($path, '.')) {
            return $doc[$path] ?? null;
        }

        $parts = explode('.', $path);
        $value = $doc;

        foreach ($parts as $p) {
            if (!is_array($value) || !array_key_exists($p, $value)) {
                return null;
            }
            $value = $value[$p];
        }

        return $value;
    }

    protected function match(array $doc, array $query): bool
    {
        foreach ($query as $field => $condition) {

            // Logical operators
            if ($field === '$and') {
                foreach ($condition as $sub) {
                    if (!$this->match($doc, $sub)) return false;
                }
                continue;
            }

            if ($field === '$or') {
                foreach ($condition as $sub) {
                    if ($this->match($doc, $sub)) return true;
                }
                return false;
            }

            if ($field === '$not') {
                return !$this->match($doc, $condition);
            }

            // Field-level comparison
            $value = $this->getValue($doc, $field);

            if (is_array($condition)) {
                foreach ($condition as $op => $expected) {
                    switch ($op) {
                        case '$eq': if ($value !== $expected) return false; break;
                        case '$ne': if ($value === $expected) return false; break;
                        case '$gt': if ($value <= $expected) return false; break;
                        case '$lt': if ($value >= $expected) return false; break;
                        case '$in': if (!in_array($value, $expected)) return false; break;
                        case '$regex':
                            if (!preg_match($expected, (string)$value)) return false;
                            break;
                    }
                }
            } else {
                if ($value !== $condition) return false;
            }
        }

        return true;
    }

    public function find(string $collection, array $query = []): array
    {
        $docs = $this->load($collection);
        if (!$query) return $docs;

        return array_values(array_filter($docs, fn($d) => $this->match($d, $query)));
    }

    public function findOne(string $collection, array $query = []): ?array
    {
        $results = $this->find($collection, $query);
        return $results[0] ?? null;
    }

    public function update(string $collection, array $query, array $update): int
    {
        $docs = $this->load($collection);
        $count = 0;

        foreach ($docs as &$doc) {
            if ($this->match($doc, $query)) {
                foreach ($update as $k => $v) {
                    if ($k !== '_id') {
                        $doc[$k] = $v;
                    }
                }
                $count++;
            }
        }

        if ($count > 0) {
            $this->save($collection, $docs);
        }

        return $count;
    }

    public function delete(string $collection, array $query): int
    {
        $docs = $this->load($collection);
        $remaining = [];
        $count = 0;

        foreach ($docs as $doc) {
            if ($this->match($doc, $query)) {
                $count++;
            } else {
                $remaining[] = $doc;
            }
        }

        if ($count > 0) {
            $this->save($collection, $remaining);
        }

        return $count;
    }
}
