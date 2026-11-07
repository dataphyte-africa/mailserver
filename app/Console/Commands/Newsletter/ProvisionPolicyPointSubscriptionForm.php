<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\SubscriberGroup;
use App\Services\Forms\ProductFormService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Form;

class ProvisionPolicyPointSubscriptionForm extends Command
{
    protected $signature = 'newsletter:provision-policy-point-subscription-form {--apply : Assign the legacy Policy Point audience and create its hosted signup form}';

    protected $description = 'Upgrade Policy Point signup without copying or remapping existing subscriber memberships.';

    public function handle(ProductFormService $forms): int
    {
        $organisation = Organisation::query()->where('primary_collection_handle', 'policy_point_newsletters')
            ->where('status', 'active')->first();
        $legacy = Form::find('policy_point_subscribe');
        $groupId = $legacy?->get('newsletter_group');
        $group = is_numeric($groupId) ? SubscriberGroup::query()->find((int) $groupId) : null;
        $products = $organisation ? Product::query()->where('organisation_id', $organisation->id)
            ->where('primary_collection_handle', 'policy_point_newsletters')->where('status', 'active')->get() : collect();
        $product = $products->count() === 1 ? $products->first() : null;

        if (! $organisation || ! $legacy || ! $group || ! $product
            || $group->collection_handle !== 'policy_point_newsletters' || $group->isArchived()
            || ($group->organisation_id && (int) $group->organisation_id !== (int) $organisation->id)
            || ($group->product_id && (int) $group->product_id !== (int) $product->id)
            || $group->isPrivateAudience()
            || ! $group->subGroups()->where('purpose', 'preference')->whereNull('archived_at')->exists()) {
            $this->error('Policy Point legacy form, one active product, and its matching preference audience are required. No changes made.');

            return self::FAILURE;
        }

        $otherGroup = SubscriberGroup::query()->where('product_id', $product->id)
            ->whereKeyNot($group->id)->where('is_public_preference', true)->whereNull('archived_at')->exists();
        if ($otherGroup) {
            $this->error('A different public Policy Point audience exists. Resolve its memberships before upgrading.');

            return self::FAILURE;
        }

        $existing = ProductForm::query()->where('slug', 'policy-point-subscribe')->first();
        if ($existing && ((int) $existing->organisation_id !== (int) $organisation->id
            || $existing->mode !== 'subscription' || $existing->form_scope !== 'organisation')) {
            $this->error('The form slug belongs to a different owner or form type. No changes made.');

            return self::FAILURE;
        }

        $this->line('Existing audience: '.$group->name.' (ID '.$group->id.')');
        $this->line('Existing preferences: '.$group->subGroups()->where('purpose', 'preference')
            ->whereNull('archived_at')->orderBy('name')->pluck('name')->implode(', '));
        if ($existing) {
            $this->info('Hosted form already exists. Editor settings and audience publication were not changed.');

            return self::SUCCESS;
        }
        if (! $this->option('apply')) {
            $this->info('Preview only. Re-run with --apply to assign ownership and create the form.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($forms, $organisation, $product, $group): void {
            $group->update([
                'organisation_id' => $organisation->id,
                'product_id' => $product->id,
                'audience_scope' => 'product_public',
                'is_public_preference' => true,
            ]);

            $forms->createForOrganisation($organisation, [
                'name' => 'Policy Point Subscription',
                'slug' => 'policy-point-subscribe',
                'template_family' => 'subscription_basic',
                'status' => 'published',
                'headline' => 'Stay informed with Policy Point',
                'description' => 'Choose how often you want to receive Policy Point analysis and updates.',
                'success_message' => 'Thank you for subscribing to Policy Point.',
                'field_definitions' => [
                    ['handle' => 'first_name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                    ['handle' => 'last_name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                    ['handle' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                    ['handle' => 'country', 'label' => 'Country', 'type' => 'country', 'required' => false],
                    ['handle' => 'organisation', 'label' => 'Organisation', 'type' => 'text', 'required' => false],
                    ['handle' => 'profession', 'label' => 'Profession', 'type' => 'text', 'required' => false],
                    ['handle' => 'consent', 'label' => 'I agree to receive Policy Point newsletters and analysis at the frequency I select.', 'type' => 'checkbox', 'required' => true],
                ],
                'public_preference_group_ids' => [$group->id],
                'settings' => ['show_banner' => true],
            ]);
        });

        $this->info('Policy Point audience ownership assigned and hosted form created at /forms/policy-point-subscribe.');

        return self::SUCCESS;
    }
}
