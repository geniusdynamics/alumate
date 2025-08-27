<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;

class ComponentCollection extends Collection
{
    /**
     * Filter components by category
     */
    public function byCategory(string $category): self
    {
        return $this->filter(fn (Component $component) => $component->category === $category);
    }

    /**
     * Filter components by type
     */
    public function byType(string $type): self
    {
        return $this->filter(fn (Component $component) => $component->type === $type);
    }

    /**
     * Get only active components
     */
    public function active(): self
    {
        return $this->filter(fn (Component $component) => $component->is_active);
    }

    /**
     * Get only inactive components
     */
    public function inactive(): self
    {
        return $this->filter(fn (Component $component) => ! $component->is_active);
    }

    /**
     * Group components by category
     */
    public function groupByCategory(): Collection
    {
        return $this->groupBy('category');
    }

    /**
     * Group components by type
     */
    public function groupByType(): Collection
    {
        return $this->groupBy('type');
    }

    /**
     * Get components with specific configuration key
     */
    public function withConfigKey(string $key): self
    {
        return $this->filter(fn (Component $component) => $component->hasConfigKey($key));
    }

    /**
     * Get components with specific configuration value
     */
    public function withConfigValue(string $key, mixed $value): self
    {
        return $this->filter(fn (Component $component) => $component->getConfigValue($key) === $value);
    }

    /**
     * Sort components by name
     */
    public function sortByName(bool $descending = false): self
    {
        return $descending ? $this->sortByDesc('name') : $this->sortBy('name');
    }

    /**
     * Sort components by creation date
     */
    public function sortByCreated(bool $descending = true): self
    {
        return $descending ? $this->sortByDesc('created_at') : $this->sortBy('created_at');
    }

    /**
     * Sort components by update date
     */
    public function sortByUpdated(bool $descending = true): self
    {
        return $descending ? $this->sortByDesc('updated_at') : $this->sortBy('updated_at');
    }

    /**
     * Get components that have instances
     */
    public function withInstances(): self
    {
        return $this->filter(fn (Component $component) => $component->instances()->exists());
    }

    /**
     * Get components without instances
     */
    public function withoutInstances(): self
    {
        return $this->filter(fn (Component $component) => ! $component->instances()->exists());
    }

    /**
     * Search components by name or description
     */
    public function searchComponents(string $query): self
    {
        $query = strtolower($query);

        return $this->filter(function (Component $component) use ($query) {
            return str_contains(strtolower($component->name), $query) ||
                   str_contains(strtolower($component->description ?? ''), $query) ||
                   str_contains(strtolower($component->type), $query);
        });
    }

    /**
     * Get usage statistics for components
     */
    public function getUsageStats(): array
    {
        $stats = [
            'total' => $this->count(),
            'active' => $this->active()->count(),
            'inactive' => $this->inactive()->count(),
            'by_category' => [],
            'with_instances' => $this->withInstances()->count(),
            'without_instances' => $this->withoutInstances()->count(),
        ];

        foreach (Component::CATEGORIES as $category) {
            $stats['by_category'][$category] = $this->byCategory($category)->count();
        }

        return $stats;
    }

    /**
     * Get the most recently updated components
     */
    public function recent(int $limit = 10): self
    {
        return $this->sortByUpdated()->take($limit);
    }

    /**
     * Get components by version
     */
    public function byVersion(string $version): self
    {
        return $this->filter(fn (Component $component) => $component->version === $version);
    }

    /**
     * Get the latest version of each component type
     */
    public function latestVersions(): self
    {
        return $this->groupBy('type')
            ->map(fn ($components) => $components->sortByDesc('version')->first())
            ->values();
    }

    /**
     * Validate all components in the collection
     */
    public function validateAll(): array
    {
        $results = [];

        foreach ($this as $component) {
            $results[$component->id] = [
                'valid' => $component->validateConfig(),
                'component' => $component->name,
            ];
        }

        return $results;
    }

    /**
     * Get only valid components
     */
    public function valid(): self
    {
        return $this->filter(fn (Component $component) => $component->validateConfig());
    }

    /**
     * Get only invalid components
     */
    public function invalid(): self
    {
        return $this->filter(fn (Component $component) => ! $component->validateConfig());
    }
}
