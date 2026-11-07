<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\ProductForm;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use Illuminate\Console\Command;

class ProvisionFoundationSubscriptionForm extends Command
{
    protected $signature = 'newsletter:provision-foundation-subscription-form {--apply : Create the hosted Foundation signup form}';

    protected $description = 'Create a Foundation subscription form for its approved public product audiences.';

    public function handle(ProductFormService $forms, ProductFormSubscriptionService $subscriptions): int
    {
        $organisation = Organisation::query()->where('primary_collection_handle', 'foundation_newsletters')
            ->where('status', 'active')->first();
        if (! $organisation) {
            $this->error('An active Foundation organisation is required. No changes made.');

            return self::FAILURE;
        }

        $groups = $subscriptions->availablePreferencesForOrganisations([(int) $organisation->id]);
        if ($groups->isEmpty()) {
            $this->error('Foundation has no approved public product audiences. No changes made.');

            return self::FAILURE;
        }

        $existing = ProductForm::query()->where('slug', 'foundation-subscribe')->first();
        if ($existing && ((int) $existing->organisation_id !== (int) $organisation->id
            || $existing->mode !== 'subscription' || $existing->form_scope !== 'organisation')) {
            $this->error('The form slug belongs to a different owner or form type. No changes made.');

            return self::FAILURE;
        }

        $this->line('Foundation signup choices: '.$groups->pluck('product.name')->implode(', '));
        if ($existing) {
            $this->info('Foundation signup form already exists. Existing editor settings were not changed.');

            return self::SUCCESS;
        }
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply to create the form.');

            return self::SUCCESS;
        }

        $fields = [
            ['handle' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => false],
            ['handle' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => false],
            ['handle' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true],
            ['handle' => 'country', 'label' => 'Country', 'type' => 'country', 'required' => false],
            ['handle' => 'organisation', 'label' => 'Organisation', 'type' => 'text', 'required' => false],
            ['handle' => 'profession', 'label' => 'Profession', 'type' => 'text', 'required' => false],
            ['handle' => 'consent', 'label' => 'I agree to receive newsletters and product updates from Dataphyte Foundation for the products I select.', 'type' => 'checkbox', 'required' => true],
        ];

        $form = $forms->createForOrganisation($organisation, [
            'name' => 'Dataphyte Foundation Subscription',
            'slug' => 'foundation-subscribe',
            'template_family' => 'subscription_basic',
            'status' => 'published',
            'headline' => 'Subscribe to Dataphyte Foundation',
            'description' => 'Choose the newsletters you want to receive.',
            'success_message' => 'Thank you for subscribing to Dataphyte Foundation.',
            'field_definitions' => $fields,
            'public_preference_group_ids' => $groups->modelKeys(),
            'settings' => ['show_banner' => true],
        ]);

        $this->info("Created Foundation signup form at /forms/{$form->slug}.");

        return self::SUCCESS;
    }
}
