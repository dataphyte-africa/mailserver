<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Form;
use Tests\TestCase;

class ObserverApplicationPageControllerTest extends TestCase
{
    public function test_hosted_application_page_uses_domain_aware_form_links(): void
    {
        $this->makeObserverApplicationForm();
        $this->createPolicyPointProduct();

        $response = $this->get(route('newsletter.forms.show', ['form' => 'osun-election-observers']));

        $response->assertOk()
            ->assertViewHas('schemaEndpoint', 'https://join.policy.example.test/subscribe/osun-election-observers/schema')
            ->assertViewHas('submitEndpoint', 'https://join.policy.example.test/subscribe/osun-election-observers')
            ->assertViewHas('statesEndpoint', 'https://join.policy.example.test/subscribe/osun-election-observers/locations/states')
            ->assertViewHas('lgasEndpointTemplate', 'https://join.policy.example.test/subscribe/osun-election-observers/locations/states/__STATE__/lgas')
            ->assertViewHas('wardsEndpointTemplate', 'https://join.policy.example.test/subscribe/osun-election-observers/locations/lgas/__LGA__/wards');
    }

    private function makeObserverApplicationForm(): void
    {
        $group = SubscriberGroup::factory()->policyPoint()->create();

        Blueprint::make('osun-election-observers')
            ->setNamespace('forms')
            ->setContents([
                'sections' => [
                    'main' => [
                        'fields' => [
                            [
                                'handle' => 'first_name',
                                'field' => ['type' => 'text', 'display' => 'First Name'],
                            ],
                            [
                                'handle' => 'last_name',
                                'field' => ['type' => 'text', 'display' => 'Last Name'],
                            ],
                            [
                                'handle' => 'email',
                                'field' => ['type' => 'text', 'display' => 'Email', 'validate' => 'required|email'],
                            ],
                        ],
                    ],
                ],
            ])->save();

        Form::make('osun-election-observers')
            ->title('Osun Election Observers')
            ->store(true)
            ->merge([
                'newsletter_group' => $group->id,
                'newsletter_endpoint' => 'osun-election-observers',
                'newsletter_submission_mode' => 'application',
            ])->save();
    }

    private function createPolicyPointProduct(): Product
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'status' => 'active',
            'product_type' => 'newsletter',
            'public_domain' => 'policy.example.test',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => now(),
            'primary_collection_handle' => 'policy_point_newsletters',
            'fallback_to_platform_domain' => true,
        ]);
    }
}
