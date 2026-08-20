<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use Inertia\Testing\AssertableInertia;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class NewsletterDashboardWidgetTest extends TestCase
{
    public function test_authenticated_dashboard_mounts_newsletter_widget_with_pending_subscriber_summary(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        Subscriber::factory()->pending()->create();
        Subscriber::factory()->active()->create();
        Subscriber::factory()->unsubscribed()->create();
        Subscriber::factory()->bounced()->create();
        Subscriber::factory()->create(['status' => 'complained']);

        $user = StatamicUser::findByEmail('admin@mailserver.test');

        $this->assertNotNull($user);

        $response = $this
            ->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('dashboard'));

        $response->assertOk();

        $response->assertInertia(function (AssertableInertia $page): void {
            $page
                ->component('Dashboard', false)
                ->has('widgets', 1)
                ->where('widgets.0.width', 100)
                ->where('widgets.0.html', function (string $html): bool {
                    $text = preg_replace('/\s+/', ' ', trim(strip_tags($html)));

                    return str_contains($text, 'Newsletter')
                        && str_contains($text, 'Subscriber status:')
                        && str_contains($text, '1 pending')
                        && str_contains($text, '1 active')
                        && str_contains($text, '1 unsubscribed')
                        && str_contains($text, '1 bounced')
                        && str_contains($text, '1 complained');
                });
        });
    }
}
