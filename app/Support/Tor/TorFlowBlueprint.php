<?php

namespace App\Support\Tor;

use Illuminate\Support\Collection;
use Statamic\Facades\Blueprint;
use Statamic\Fields\Blueprint as StatamicBlueprint;

/**
 * The TOR editor is a native Statamic publish form. Keeping this definition in
 * PHP lets the company selector be limited to the operator's assigned scope.
 */
class TorFlowBlueprint
{
    public static function make(Collection $organisations, bool $editing = false): StatamicBlueprint
    {
        $companyOptions = $organisations
            ->mapWithKeys(fn ($organisation) => [(string) $organisation->id => $organisation->name])
            ->all();

        return Blueprint::make('tor_flow')
            ->setNamespace('tor')
            ->setContents([
                'title' => $editing ? 'Edit TOR flow' : 'Create TOR flow',
                'tabs' => [
                    'details' => [
                        'display' => 'TOR details',
                        'sections' => [[
                            'fields' => [
                                self::field('organisation_id', ['type' => 'select', 'display' => 'Company', 'instructions' => 'The company name and logo appear on the signing page and final PDF.', 'options' => $companyOptions, 'validate' => ['required']]),
                                self::field('title', ['type' => 'text', 'display' => 'TOR title', 'instructions' => 'Use a clear title signers will recognise in their invitation email.', 'validate' => ['required', 'max:255']]),
                                self::field('content_html', ['type' => 'bard', 'display' => 'TOR content', 'instructions' => 'This is rendered on the public signing page and in every signed PDF.', 'container' => 'assets', 'buttons' => ['h2', 'h3', 'bold', 'italic', 'underline', 'unorderedlist', 'orderedlist', 'anchor', 'image'], 'save_html' => true, 'remove_empty_nodes' => 'trim', 'validate' => ['required']]),
                            ],
                        ]],
                    ],
                    'signer_fields' => [
                        'display' => 'Signer fields',
                        'sections' => [[
                            'instructions' => 'Add one section for each group of information the signer must provide. Full name, signature, signing time, email and verification code remain system-controlled.',
                            'fields' => [
                                self::field('signer_fields', self::signerSectionsField()),
                            ],
                        ]],
                    ],
                    'delivery' => [
                        'display' => 'Delivery',
                        'sections' => [[
                            'fields' => [
                                self::field('internal_recipients', ['type' => 'text', 'display' => 'Internal notification recipients', 'instructions' => 'Comma-separated addresses that receive each completed signed PDF.']),
                                ...($editing ? [] : [self::field('activate', ['type' => 'toggle', 'display' => 'Activate now and prepare invitations', 'instructions' => 'You can import signers after saving.', 'default' => false])]),
                            ],
                        ]],
                    ],
                ],
            ]);
    }

    private static function signerSectionsField(): array
    {
        return [
            'type' => 'replicator',
            'display' => 'Signer sections',
            'instructions' => 'A section heading groups the fields beneath it on the public signing form and in the signed PDF.',
            'collapse' => 'accordion',
            'previews' => true,
            'sets' => [
                'section' => [
                    'display' => 'Signer section',
                    'icon' => 'text-formatting-initial-letter',
                    'fields' => [
                        self::field('heading', ['type' => 'text', 'display' => 'Section heading', 'validate' => ['required']]),
                        self::field('instructions', ['type' => 'textarea', 'display' => 'Help text', 'instructions' => 'Optional guidance shown below the heading.']),
                        self::field('fields', [
                            'type' => 'replicator',
                            'display' => 'Fields in this section',
                            'instructions' => 'Add the signer inputs that belong under this heading.',
                            'collapse' => 'accordion',
                            'previews' => true,
                            'sets' => [
                                'text' => self::inputSet('Short text', 'A single-line response, for example Bank Name.'),
                                'textarea' => self::inputSet('Long text', 'A multi-line response.'),
                                'date' => self::inputSet('Date', 'A date selected by the signer.'),
                                'checkbox' => self::inputSet('Acknowledgement checkbox', 'An acknowledgement the signer can confirm.'),
                            ],
                        ]),
                    ],
                ],
            ],
        ];
    }

    private static function inputSet(string $display, string $instructions): array
    {
        return [
            'display' => $display,
            'instructions' => $instructions,
            'fields' => [
                self::field('handle', ['type' => 'hidden']),
                self::field('label', ['type' => 'text', 'display' => 'Field label', 'validate' => ['required']]),
                self::field('help', ['type' => 'text', 'display' => 'Help text', 'instructions' => 'Optional short guidance for the signer.']),
                self::field('required', ['type' => 'toggle', 'display' => 'Required', 'default' => false]),
            ],
        ];
    }

    private static function field(string $handle, array $field): array
    {
        return ['handle' => $handle, 'field' => $field];
    }
}
