<?php

namespace App\Services\Newsletter;

use Statamic\Entries\Entry;

/**
 * Resolves the Blade view key for a given Statamic entry.
 *
 * Priority order:
 *  1. Stored `template` or `email_template` field on the entry
 *  2. Convention: emails.{collection}.{blueprint_handle}
 *  3. Collection fallback: emails.{collection}.default
 *  4. Hard fallback: emails.layout
 */
class TemplateResolver
{
    public function resolve(?object $entry, ?string $collection = null): string
    {
        if (! $entry) {
            return 'emails.layout';
        }

        // 1. Stored field — set automatically by blueprint default or selected in the CP
        $stored = $entry->get('template') ?: $entry->get('email_template');
        $stored = is_string($stored) ? str_replace('/', '.', $stored) : $stored;

        if ($stored && view()->exists($stored)) {
            return $stored;
        }

        // 2. Convention — collection/blueprint_handle must both be available
        $col       = $entry->collectionHandle()  ?? $collection;
        $blueprint = $entry->blueprint()?->handle();

        if ($col && $blueprint) {
            $viewCollection = match ($col) {
                'insight_newsletters' => 'insight',
                'foundation_newsletters' => 'foundation',
                'academy_newsletters' => 'academy',
                'policy_point_newsletters' => 'policy_point',
                default => str_replace('_newsletters', '', $col),
            };
            $viewBlueprint = match ($blueprint) {
                'insight_updates' => 'insight-update',
                default => str_replace('_', '-', $blueprint),
            };
            $convention = "emails.{$viewCollection}.{$viewBlueprint}";

            if (view()->exists($convention)) {
                return $convention;
            }
        }

        // 3. Collection default
        if ($col) {
            $colDefault = "emails.{$col}.default";
            if (view()->exists($colDefault)) {
                return $colDefault;
            }
        }

        // 4. Hard fallback
        return 'emails.layout';
    }
}
