<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\ProductForm;
use App\Models\SubscriberGroup;
use App\Services\Forms\ProductFormService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProvisionAcademySubscriptionForm extends Command
{
    protected $signature = 'newsletter:provision-academy-subscription-form {--apply : Publish the DataLab audience and create the Academy signup form}';

    protected $description = 'Prepare the Academy signup form using its existing DataLab audience and preferences.';

    public function handle(ProductFormService $forms): int
    {
        $academy = Organisation::query()->where('primary_collection_handle', 'academy_newsletters')
            ->where('status', 'active')->first();
        if (! $academy) {
            $this->error('An active Dataphyte Academy organisation is required. No changes made.');

            return self::FAILURE;
        }

        $group = SubscriberGroup::query()->where('organisation_id', $academy->id)
            ->whereHas('product', fn ($query) => $query->where('name', 'DataLab')
                ->where('status', 'active')->where('organisation_id', $academy->id)
                ->where('primary_collection_handle', 'academy_newsletters'))
            ->where('collection_handle', 'academy_newsletters')
            ->where('audience_scope', 'product_public')->whereNull('archived_at')
            ->whereHas('subGroups', fn ($query) => $query->where('purpose', 'preference')->whereNull('archived_at'))
            ->first();
        if (! $group) {
            $this->error('An active DataLab product audience with preferences is required. No changes made.');

            return self::FAILURE;
        }

        $existing = ProductForm::query()->where('slug', 'academy-subscribe')->first();
        if ($existing && ((int) $existing->organisation_id !== (int) $academy->id
            || $existing->mode !== 'subscription' || $existing->form_scope !== 'organisation')) {
            $this->error('The Academy form slug belongs to another owner or form type. No changes made.');

            return self::FAILURE;
        }

        $this->line('DataLab preferences: '.$group->subGroups()->where('purpose', 'preference')
            ->whereNull('archived_at')->orderBy('name')->pluck('name')->implode(', '));
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply to publish the audience and create the form.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($group, $existing, $academy, $forms): void {
            $group->update(['is_public_preference' => true]);
            if ($existing) {
                return;
            }

            $fields = [
                ['handle' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => false],
                ['handle' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => false],
                ['handle' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                ['handle' => 'country', 'label' => 'Country', 'type' => 'country', 'required' => false],
                ['handle' => 'organisation', 'label' => 'Organisation', 'type' => 'text', 'required' => false],
                ['handle' => 'profession', 'label' => 'Profession', 'type' => 'text', 'required' => false],
                ['handle' => 'consent', 'label' => 'I agree to receive learning updates and newsletters from Dataphyte Academy for the choices I select.', 'type' => 'checkbox', 'required' => true],
            ];
            $forms->createForOrganisation($academy, [
                'name' => 'Dataphyte Academy Subscription',
                'slug' => 'academy-subscribe',
                'template_family' => 'subscription_basic',
                'status' => 'published',
                'headline' => 'Keep learning with Dataphyte Academy',
                'description' => 'Choose the Academy learning updates you want to receive.',
                'success_message' => 'Thank you for subscribing to Dataphyte Academy.',
                'field_definitions' => $fields,
                'public_preference_group_ids' => [$group->id],
                'settings' => ['show_banner' => true],
            ]);
        });

        $this->info($existing ? 'DataLab audience published. Existing Academy form settings were preserved.'
            : 'DataLab audience published. Academy signup form created at /forms/academy-subscribe.');

        return self::SUCCESS;
    }
}
