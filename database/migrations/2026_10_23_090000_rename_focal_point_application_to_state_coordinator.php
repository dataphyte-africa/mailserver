<?php

use Illuminate\Database\Migrations\Migration;
use Statamic\Facades\GlobalSet;

return new class extends Migration
{
    public function up(): void
    {
        $set = GlobalSet::findByHandle('focal_point_application');

        if (! $set) {
            return;
        }

        $set->title('State Coordinator Application')->save();
        $variables = $set->inDefaultSite();
        $variables->data([
            ...$variables->data()->toArray(),
            'hero_intro' => 'Dataphyte Foundation seeks qualified applicants to observe the 2027 general elections and manage field observers in their state.',
            'intro' => "State Coordinators will coordinate and supervise election observers in their state and will directly report to Dataphyte Foundation's focal person. Applicants must be residents of the state they are applying to coordinate.",
            'selection_note' => 'Submitting an application does not guarantee selection. We will contact applicants about their status after the deadline.',
            'preparation_note' => 'Prepare your CV (maximum two pages) and certificate before you begin your application.',
            'closes_at' => '2026-10-28 23:59:00',
            'tor_html' => view('foundation.focal-points._terms')->render(),
        ]);
        $variables->save();
    }

    public function down(): void
    {
        // Content changes are intentional editorial updates and are not reversed automatically.
    }
};
