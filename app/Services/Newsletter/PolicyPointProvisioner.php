<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Form;

class PolicyPointProvisioner
{
    public function provision(): array
    {
        return [
            'collection' => $this->ensureCollection(),
            'blueprint' => $this->ensureCollectionBlueprint(),
            'group' => $this->ensureSubscriberGroup(),
            'form_blueprint' => $this->ensureFormBlueprint(),
            'form' => $this->ensureForm(),
        ];
    }

    private function ensureCollection(): string
    {
        $collection = Collection::findByHandle('policy_point_newsletters')
            ?? Collection::make('policy_point_newsletters');

        $collection
            ->title('Policy Point Newsletters')
            ->routes('/newsletters/policy-point/{slug}')
            ->dated(true)
            ->sortDirection('desc')
            ->save();

        return 'policy_point_newsletters';
    }

    private function ensureCollectionBlueprint(): string
    {
        $blueprint = Blueprint::find('collections.policy_point_newsletters.policy_point')
            ?? Blueprint::make('policy_point')->setNamespace('collections.policy_point_newsletters');

        $blueprint
            ->setContents($this->collectionBlueprintContents())
            ->save();

        return 'collections.policy_point_newsletters.policy_point';
    }

    private function ensureSubscriberGroup(): int
    {
        $group = SubscriberGroup::query()->updateOrCreate(
            ['slug' => 'policy-point-subscribers'],
            [
                'name' => 'Policy Point Subscribers',
                'collection_handle' => 'policy_point_newsletters',
                'description' => 'Subscribers collected through the Policy Point subscription form.',
            ],
        );

        return $group->id;
    }

    private function ensureFormBlueprint(): string
    {
        $blueprint = Blueprint::find('forms.policy_point_subscribe')
            ?? Blueprint::make('policy_point_subscribe')->setNamespace('forms');

        $blueprint
            ->setContents($this->formBlueprintContents())
            ->save();

        return 'forms.policy_point_subscribe';
    }

    private function ensureForm(): string
    {
        $groupId = SubscriberGroup::query()
            ->where('slug', 'policy-point-subscribers')
            ->value('id');

        $form = Form::find('policy_point_subscribe')
            ?? Form::make('policy_point_subscribe');

        $form
            ->title('policy-point-subscribe')
            ->store(true)
            ->honeypot('honeypot')
            ->merge([
                'newsletter_group' => $groupId,
                'newsletter_endpoint' => 'policy-point',
                'newsletter_preference_field' => 'preference',
                'newsletter_logo_url' => null,
                'newsletter_brand_color' => null,
                'newsletter_privacy_url' => null,
                'newsletter_success_message' => null,
                'newsletter_send_confirmation_email' => true,
                'newsletter_send_update_email' => true,
                'newsletter_confirmation_subject' => 'Thank you! You are subscribed to receive Dataphyte Policy Point updates.',
                'newsletter_resubscribe_subject' => 'Welcome Back! You have re-subscribed to Dataphyte Policy Point updates.',
                'newsletter_confirmation_body' => "Thank you for subscribing to receive Dataphyte Policy Point updates.\n\nYou will now receive our latest policy insights and updates in your inbox based on the preference you selected.\n\nIf you ever want to change how often you hear from us or unsubscribe, you can use the links in the footer of any email we send.\n\nBest regards,\nDataphyte Policy Point",
                'newsletter_resubscribe_body' => "Welcome back, and thank you for re-subscribing to Dataphyte Policy Point updates.\n\nYou will start receiving our latest policy insights and updates in your inbox again based on the preference you selected.\n\nIf you would like to manage how often you hear from us or unsubscribe at any time, you can use the links in the footer of any email we send.\n\nBest regards,\nDataphyte Policy Point",
            ])
            ->save();

        return $form->handle();
    }

    private function collectionBlueprintContents(): array
    {
        return [
            'title' => 'Policy Point',
            'sections' => [
                'main' => [
                    'display' => 'Content',
                    'fields' => [
                        [
                            'handle' => 'subject',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Subject Line',
                                'instructions' => 'The email subject line shown in the inbox.',
                                'validate' => 'required',
                                'listable' => true,
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'preheader',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Preheader',
                                'instructions' => 'Short preview text after the subject in most inboxes (50–90 chars).',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'hero_image',
                            'field' => [
                                'type' => 'assets',
                                'display' => 'Hero Image',
                                'container' => 'assets',
                                'max_files' => 1,
                                'restrict' => false,
                                'allow_uploads' => true,
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'content',
                            'field' => [
                                'type' => 'bard',
                                'display' => 'Content',
                                'validate' => 'required',
                                'buttons' => [
                                    'h2', 'h3', 'bold', 'italic', 'underline',
                                    'unorderedlist', 'orderedlist', 'quote',
                                    'anchor', 'image',
                                ],
                                'save_html' => true,
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'rss_feed_url',
                            'field' => [
                                'type' => 'text',
                                'display' => 'RSS Feed URL',
                                'instructions' => 'Optional RSS endpoint used to fetch newsletter story cards, for example https://dataphyte.com/rss/policy_point.xml.',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'rss_item_limit',
                            'field' => [
                                'type' => 'integer',
                                'display' => 'RSS Item Limit',
                                'instructions' => 'How many stories to fetch from the RSS feed for this newsletter issue.',
                                'default' => 6,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'refresh_rss_items',
                            'field' => [
                                'type' => 'toggle',
                                'display' => 'Refresh RSS Stories On Save',
                                'instructions' => 'Turn on to repopulate the story list from the RSS feed the next time this entry is saved.',
                                'default' => false,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'rss_items',
                            'field' => [
                                'type' => 'replicator',
                                'display' => 'RSS Stories',
                                'instructions' => 'Fetched stories appear here. Reorder rows to control newsletter order and toggle one row as the lead story.',
                                'collapse' => true,
                                'previews' => true,
                                'fullscreen' => true,
                                'sets' => [
                                    'story' => [
                                        'display' => 'Story',
                                        'fields' => $this->storyFields(),
                                    ],
                                ],
                            ],
                        ],
                        [
                            'handle' => 'template',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Email Template',
                                'visibility' => 'hidden',
                                'default' => 'emails.policy_point.policy-point',
                                'listable' => false,
                                'width' => 100,
                            ],
                        ],
                    ],
                ],
                'sidebar' => [
                    'display' => 'Settings',
                    'fields' => [
                        [
                            'handle' => 'audiences',
                            'field' => [
                                'type' => 'terms',
                                'display' => 'Send To (Audiences)',
                                'instructions' => 'Select the sub-groups that will receive this newsletter.',
                                'taxonomies' => ['newsletter_audiences'],
                                'mode' => 'select',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'send_to_all',
                            'field' => [
                                'type' => 'toggle',
                                'display' => 'Send to All',
                                'instructions' => 'Override audience selection and send to every subscriber in this group.',
                                'default' => false,
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'author',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Author / Byline',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'reply_to',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Reply-To (override)',
                                'instructions' => 'Leave blank to use the collection default.',
                                'input_type' => 'email',
                                'width' => 100,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function formBlueprintContents(): array
    {
        return [
            'tabs' => [
                'main' => [
                    'display' => 'Main',
                    'sections' => [
                        [
                            'fields' => [
                                [
                                    'handle' => 'firstname',
                                    'field' => [
                                        'type' => 'text',
                                        'antlers' => true,
                                        'display' => 'Firstname',
                                    ],
                                ],
                                [
                                    'handle' => 'lastname',
                                    'field' => [
                                        'type' => 'text',
                                        'display' => 'Lastname',
                                    ],
                                ],
                                [
                                    'handle' => 'email',
                                    'field' => [
                                        'type' => 'text',
                                        'antlers' => true,
                                        'display' => 'Email',
                                        'input_type' => 'email',
                                    ],
                                ],
                                [
                                    'handle' => 'preference',
                                    'field' => [
                                        'type' => 'select',
                                        'display' => 'Preference',
                                        'options' => [
                                            ['key' => 'regular', 'value' => 'Regular'],
                                            ['key' => 'monthly', 'value' => 'monthly'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function storyFields(): array
    {
        return [
            ['handle' => 'is_lead', 'field' => ['type' => 'toggle', 'display' => 'Lead Story', 'width' => 25, 'replicator_preview' => false]],
            ['handle' => 'title', 'field' => ['type' => 'text', 'display' => 'Title', 'width' => 100]],
            ['handle' => 'url', 'field' => ['type' => 'text', 'display' => 'URL', 'width' => 100, 'replicator_preview' => false]],
            ['handle' => 'image_url', 'field' => ['type' => 'text', 'display' => 'Image URL', 'width' => 100, 'replicator_preview' => false]],
            ['handle' => 'excerpt', 'field' => ['type' => 'textarea', 'display' => 'Excerpt', 'width' => 100, 'replicator_preview' => false]],
            ['handle' => 'author', 'field' => ['type' => 'text', 'display' => 'Author', 'width' => 50, 'replicator_preview' => false]],
            ['handle' => 'published_label', 'field' => ['type' => 'text', 'display' => 'Published Label', 'width' => 50, 'replicator_preview' => false]],
            ['handle' => 'primary_taxonomy_title', 'field' => ['type' => 'text', 'display' => 'Category', 'width' => 50, 'replicator_preview' => false]],
        ];
    }
}
