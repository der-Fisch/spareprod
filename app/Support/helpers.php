<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $value): string
    {
        return 'Rp' . number_format((float) ($value ?? 0), 0, ',', '.');
    }
}

if (! function_exists('rupiah_catalog')) {
    function rupiah_catalog(float|int|string|null $value): string
    {
        return rupiah((float) ($value ?? 0) * 10000);
    }
}

if (! function_exists('avatar_initials')) {
    function avatar_initials(mixed $value): string
    {
        if (is_object($value)) {
            $source = trim((string) (($value->first_name ?: $value->username ?: $value->email ?: $value->name) ?? 'User'));
        } else {
            $source = trim((string) $value);
        }

        $parts = preg_split('/\s+/', $source) ?: [];

        return collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'U';
    }
}

if (! function_exists('resolve_path_value')) {
    function resolve_path_value(mixed $target, string $path): mixed
    {
        $current = $target;

        foreach (explode('.', $path) as $bit) {
            if ($current === null) {
                return null;
            }

            if ($current instanceof Collection && $bit === 'count') {
                $current = $current->count();
                continue;
            }

            if (is_array($current) && array_key_exists($bit, $current)) {
                $current = $current[$bit];
                continue;
            }

            if ($current instanceof Model || is_object($current)) {
                if (isset($current->{$bit}) || property_exists($current, $bit)) {
                    $current = $current->{$bit};
                    continue;
                }

                if (method_exists($current, $bit)) {
                    $next = $current->{$bit}();
                    $current = $next instanceof Relation ? $next->getResults() : $next;
                    continue;
                }
            }

            return null;
        }

        return $current;
    }
}
