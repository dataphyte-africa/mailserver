<?php

use Illuminate\Database\Migrations\Migration;
use Statamic\Facades\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['weekly', 'activities', 'project_update', 'foundation_newsletters'] as $handle) {
            $blueprint = Blueprint::find("collections.foundation_newsletters.{$handle}");
            if (! $blueprint) {
                continue;
            }

            $contents = $blueprint->contents();
            if (! isset($contents['tabs'])) {
                continue;
            }
            $changed = false;
            foreach ($contents['tabs'] as &$tab) {
                if (! isset($tab['sections'])) {
                    continue;
                }
                foreach ($tab['sections'] as &$section) {
                    if (! isset($section['fields'])) {
                        continue;
                    }
                    foreach ($section['fields'] as &$field) {
                        if (($field['handle'] ?? null) === 'author') {
                            $field['handle'] = 'byline';
                            $changed = true;
                        }
                    }
                    unset($field);
                }
                unset($section);
            }
            unset($tab);

            if ($changed) {
                $blueprint->setContents($contents)->save();
            }
        }
    }

    public function down(): void
    {
        // Restoring the reserved author field would break Statamic's author metadata.
    }
};
