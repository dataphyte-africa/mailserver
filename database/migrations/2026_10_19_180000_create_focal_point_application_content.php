<?php

use Illuminate\Database\Migrations\Migration;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Site;

return new class extends Migration
{
    public function up(): void
    {
        if (GlobalSet::findByHandle('focal_point_application')) {
            return;
        }

        $set = GlobalSet::make('focal_point_application')->title('State Focal Point Application');
        $set->save();
        $variables = $set->makeLocalization(Site::default()->handle());
        $variables->data([
            'hero_intro' => "Help coordinate election observers across Nigeria for Dataphyte Foundation's 2027 election observation exercise.",
            'intro' => "State Focal Points will recruit, coordinate, supervise and train observers in their state, and report to the Foundation's focal person. Applicants must reside in the state they apply to coordinate.",
            'selection_note' => 'Submitting an application does not guarantee selection. We will contact applicants about their status after the deadline.',
            'preparation_note' => 'Prepare your CV (maximum two pages) and highest-qualification certificate before you begin.',
            'closes_at' => config('focal_point_applications.closes_at'),
            'accepting_submissions' => true,
            'tor_html' => view('foundation.focal-points._terms')->render(),
        ]);
        $variables->save();
    }

    public function down(): void
    {
        GlobalSet::findByHandle('focal_point_application')?->delete();
    }
};
