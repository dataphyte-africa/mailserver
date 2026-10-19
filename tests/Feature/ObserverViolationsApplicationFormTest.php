<?php

namespace Tests\Feature;

use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class ObserverViolationsApplicationFormTest extends TestCase
{
    public function test_admin_can_find_the_bespoke_form_and_its_public_page(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $admin = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($admin);

        $this->actingAs($admin, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('product-forms.index'))
            ->assertOk()
            ->assertSee('Observer Violations')
            ->assertSee(route('observer-violations.index'));

        $this->get(route('observer-violations.index'))
            ->assertOk()
            ->assertSee('Observer Violation Intake')
            ->assertViewHas('submitEndpoint', route('observer-violations.reports.store'));
    }

    public function test_bespoke_form_is_not_shown_among_subscription_forms(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $admin = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($admin);

        $this->actingAs($admin, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('subscription-forms.index'))
            ->assertOk()
            ->assertDontSee('Observer Violations');
    }
}
