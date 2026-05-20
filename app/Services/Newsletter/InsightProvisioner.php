<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Form;

class InsightProvisioner
{
    private const COLLECTION = 'insight_newsletters';
    private const GROUP_SLUG = 'insight-subscribers';
    private const FORM_HANDLE = 'insight_subscribe';
    private const FORM_ENDPOINT = 'insight';

    private const BLUEPRINTS = [
        ['handle' => 'pocket_science', 'title' => 'Pocket Science', 'template' => 'emails.insight.pocket-science', 'feed_label' => 'Pocket Science'],
        ['handle' => 'senorrita', 'title' => 'SenorRita', 'template' => 'emails.insight.senorrita', 'feed_label' => 'SenorRita'],
        ['handle' => 'marina_maitama', 'title' => 'Marina and Maitama', 'template' => 'emails.insight.marina-maitama', 'feed_label' => 'Marina and Maitama'],
        ['handle' => 'data_dive', 'title' => 'Data Dive', 'template' => 'emails.insight.data-dive', 'feed_label' => 'Data Dive'],
    ];

    private const PREFERENCES = [
        ['key' => 'pocket-science', 'value' => 'Pocket Science'],
        ['key' => 'senorrita', 'value' => 'SenorRita'],
        ['key' => 'marina-maitama', 'value' => 'Marina and Maitama'],
        ['key' => 'data-dive', 'value' => 'Data Dive'],
    ];

    public function provision(): array
    {
        return [
            'collection' => $this->ensureCollection(),
            'blueprints' => $this->ensureCollectionBlueprints(),
            'group' => $this->ensureSubscriberGroup(),
            'form_blueprint' => $this->ensureFormBlueprint(),
            'form' => $this->ensureForm(),
        ];
    }

    private function ensureCollection(): string
    {
        $collection = Collection::findByHandle(self::COLLECTION)
            ?? Collection::make(self::COLLECTION);

        $collection
            ->title('Insight Newsletters')
            ->routes('/newsletters/insight/{slug}')
            ->dated(true)
            ->sortDirection('desc')
            ->save();

        return self::COLLECTION;
    }

    private function ensureCollectionBlueprints(): array
    {
        $namespace = 'collections.' . self::COLLECTION;
        $expected = collect(self::BLUEPRINTS)->pluck('handle')->all();

        foreach (Blueprint::in($namespace)->all() as $existing) {
            if (! in_array($existing->handle(), $expected, true)) {
                $existing->delete();
            }
        }

        $keys = [];

        foreach (self::BLUEPRINTS as $definition) {
            $key = $namespace . '.' . $definition['handle'];
            $blueprint = Blueprint::find($key)
                ?? Blueprint::make($definition['handle'])->setNamespace($namespace);

            $blueprint
                ->setContents($this->collectionBlueprintContents($definition))
                ->save();

            $keys[] = $key;
        }

        return $keys;
    }

    private function ensureSubscriberGroup(): int
    {
        $group = SubscriberGroup::query()->updateOrCreate(
            ['slug' => self::GROUP_SLUG],
            [
                'name' => 'Insight Subscribers',
                'collection_handle' => self::COLLECTION,
                'description' => 'Subscribers collected through the Dataphyte Insight subscription form.',
            ],
        );

        $expectedSlugs = collect(self::PREFERENCES)->pluck('key')->all();

        foreach (self::PREFERENCES as $preference) {
            SubscriberSubGroup::query()->updateOrCreate(
                [
                    'subscriber_group_id' => $group->id,
                    'slug' => $preference['key'],
                ],
                [
                    'name' => $preference['value'],
                ],
            );
        }

        SubscriberSubGroup::query()
            ->where('subscriber_group_id', $group->id)
            ->whereNotIn('slug', $expectedSlugs)
            ->delete();

        return $group->id;
    }

    private function ensureFormBlueprint(): string
    {
        $blueprint = Blueprint::find('forms.' . self::FORM_HANDLE)
            ?? Blueprint::make(self::FORM_HANDLE)->setNamespace('forms');

        $blueprint
            ->setContents($this->formBlueprintContents())
            ->save();

        return 'forms.' . self::FORM_HANDLE;
    }

    private function ensureForm(): string
    {
        $groupId = SubscriberGroup::query()
            ->where('slug', self::GROUP_SLUG)
            ->value('id');

        $form = Form::find(self::FORM_HANDLE)
            ?? Form::make(self::FORM_HANDLE);

        $form
            ->title('insight-subscribe')
            ->store(true)
            ->honeypot('honeypot')
            ->merge([
                'newsletter_group' => $groupId,
                'newsletter_endpoint' => self::FORM_ENDPOINT,
                'newsletter_preference_field' => 'preference',
                'newsletter_logo_url' => null,
                'newsletter_brand_color' => null,
                'newsletter_privacy_url' => null,
                'newsletter_success_message' => 'Thanks for subscribing to Dataphyte Insight.',
                'newsletter_send_confirmation_email' => true,
                'newsletter_send_update_email' => false,
                'newsletter_confirmation_subject' => 'Thank you! You are subscribed to receive Dataphyte Insight updates.',
                'newsletter_resubscribe_subject' => 'Welcome Back! You have re-subscribed to Dataphyte Insight updates.',
                'newsletter_confirmation_body' => "Thank you for subscribing to receive Dataphyte Insight updates.\n\nYou will now receive our latest newsletters based on the preference you selected.\n\nIf you ever want to change how often you hear from us or unsubscribe, you can use the links in the footer of any email we send.\n\nBest regards,\nDataphyte Insight",
                'newsletter_resubscribe_body' => "Welcome back, and thank you for re-subscribing to Dataphyte Insight updates.\n\nYou will start receiving our latest newsletters again based on the preference you selected.\n\nIf you would like to manage your preferences or unsubscribe at any time, you can use the links in the footer of any email we send.\n\nBest regards,\nDataphyte Insight",
            ])
            ->save();

        return $form->handle();
    }

    private function collectionBlueprintContents(array $definition): array
    {
        return [
            'title' => $definition['title'],
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
                                'display' => 'RSS 1 Feed URL',
                                'instructions' => 'Primary RSS source for ' . $definition['feed_label'] . '. This feed populates the lead story and the reorderable story list.',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'rss_item_limit',
                            'field' => [
                                'type' => 'integer',
                                'display' => 'RSS 1 Item Limit',
                                'instructions' => 'How many stories to fetch from the primary RSS feed for this newsletter issue.',
                                'default' => 6,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'refresh_rss_items',
                            'field' => [
                                'type' => 'toggle',
                                'display' => 'Refresh RSS 1 Stories On Save',
                                'instructions' => 'Turn on to repopulate the primary story list from RSS 1 the next time this entry is saved.',
                                'default' => false,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'rss_items',
                            'field' => [
                                'type' => 'grid',
                                'display' => 'RSS 1 Stories',
                                'instructions' => 'Fetched primary stories appear here. Reorder rows to control newsletter order and toggle one row as the lead story.',
                                'mode' => 'stacked',
                                'reorderable' => true,
                                'fullscreen' => true,
                                'add_row' => 'Add Story',
                                'fields' => [
                                    ['handle' => 'is_lead', 'field' => ['type' => 'toggle', 'display' => 'Lead Story', 'width' => 25]],
                                    ['handle' => 'title', 'field' => ['type' => 'text', 'display' => 'Title', 'width' => 100]],
                                    ['handle' => 'url', 'field' => ['type' => 'text', 'display' => 'URL', 'width' => 100]],
                                    ['handle' => 'image_url', 'field' => ['type' => 'text', 'display' => 'Image URL', 'width' => 100]],
                                    ['handle' => 'excerpt', 'field' => ['type' => 'textarea', 'display' => 'Excerpt', 'width' => 100]],
                                    ['handle' => 'author', 'field' => ['type' => 'text', 'display' => 'Author', 'width' => 50]],
                                    ['handle' => 'published_label', 'field' => ['type' => 'text', 'display' => 'Published Label', 'width' => 50]],
                                    ['handle' => 'primary_taxonomy_title', 'field' => ['type' => 'text', 'display' => 'Category', 'width' => 50]],
                                ],
                            ],
                        ],
                        [
                            'handle' => 'related_rss_feed_url',
                            'field' => [
                                'type' => 'text',
                                'display' => 'RSS 2 Feed URL',
                                'instructions' => 'Related articles feed across Dataphyte newsletters.',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'related_rss_item_limit',
                            'field' => [
                                'type' => 'integer',
                                'display' => 'RSS 2 Item Limit',
                                'instructions' => 'How many related articles to fetch from RSS 2.',
                                'default' => 4,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'recommended_rss_feed_url',
                            'field' => [
                                'type' => 'text',
                                'display' => 'RSS 3 Feed URL',
                                'instructions' => 'Recommended reads feed for trending socio-economic issues.',
                                'width' => 100,
                            ],
                        ],
                        [
                            'handle' => 'recommended_rss_item_limit',
                            'field' => [
                                'type' => 'integer',
                                'display' => 'RSS 3 Item Limit',
                                'instructions' => 'How many recommended stories to fetch from RSS 3.',
                                'default' => 4,
                                'width' => 50,
                            ],
                        ],
                        [
                            'handle' => 'template',
                            'field' => [
                                'type' => 'text',
                                'display' => 'Email Template',
                                'visibility' => 'hidden',
                                'default' => $definition['template'],
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
                                        'options' => self::PREFERENCES,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
