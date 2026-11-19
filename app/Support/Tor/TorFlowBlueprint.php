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
    public static function make(Collection $organisations, bool $editing = false, bool $allowActivation = false): StatamicBlueprint
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
                                self::field('signing_deadline', ['type' => 'date', 'display' => 'Signing deadline', 'instructions' => 'Optional. Unfinished links stop accepting signatures after this date and time. Edit it later to extend or reopen the TOR.', 'mode' => 'single', 'time_enabled' => true, 'time_seconds_enabled' => false, 'validate' => ['nullable', 'date']]),
                                self::field('internal_recipients', ['type' => 'text', 'display' => 'Internal notification recipients', 'instructions' => 'Comma-separated addresses that receive each completed signed PDF.']),
                                self::field('invitation_email_copy', self::emailCopyField('Invitation email copy', 'Optional message shown after the greeting. Use :full_name, :organisation_name, and :tor_title. The secure signing button remains automatic.', ':organisation_name has invited you to read and sign :tor_title.')),
                                self::field('signer_completion_email_copy', self::emailCopyField('Signer completion email copy', 'Optional message shown after the greeting when the signer completes. Use :full_name, :organisation_name, and :tor_title. The original-copy button remains automatic.', 'You have just completed the signing of :tor_title.')),
                                self::field('organisation_completion_email_copy', self::emailCopyField('Organisation completion email copy', 'Optional alert message when a signer completes. Use :full_name, :organisation_name, and :tor_title. The organisation-copy button remains automatic.', ':full_name has just completed the signing of :tor_title.')),
                                ...(! $editing || $allowActivation ? [self::field('activate', ['type' => 'toggle', 'display' => $editing ? 'Activate TOR' : 'Activate now and prepare invitations', 'instructions' => $editing ? 'Make this draft available for signer imports and invitations.' : 'You can import signers after saving.', 'default' => false])] : []),
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
                                'bank_account' => self::inputSet('Verified bank account', 'A bank, account number and account name verified through Paystack.'),
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

    private static function emailCopyField(string $display, string $instructions, string $default): array
    {
        return ['type' => 'textarea', 'display' => $display, 'instructions' => $instructions, 'default' => $default, 'placeholder' => $default, 'validate' => ['nullable', 'max:5000']];
    }

    private static function field(string $handle, array $field): array
    {
        return ['handle' => $handle, 'field' => $field];
    }
}
