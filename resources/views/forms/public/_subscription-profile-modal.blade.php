@if($profilePending)
    <style>
        .profile-overlay { position: fixed; inset: 0; z-index: 100; display: grid; place-items: center; padding: 20px; background: rgba(9, 30, 65, .65); }
        .profile-dialog { width: min(100%, 590px); max-height: min(90vh, 850px); overflow-y: auto; padding: 28px; border-radius: 14px; background: #fff; color: #091e41; box-shadow: 0 20px 70px rgba(9, 30, 65, .25); }
        .profile-dialog h2 { margin: 0 0 9px; font-size: 23px; line-height: 1.25; }
        .profile-dialog p { margin: 0 0 20px; color: #536078; }
        .profile-dialog__fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .profile-dialog__field { min-width: 0; }
        .profile-dialog__field--wide { grid-column: 1 / -1; }
        .profile-dialog__field label, .profile-dialog__field .country-picker label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 700; }
        .profile-dialog__field input, .profile-dialog__field select, .profile-dialog__field textarea, .profile-dialog__field .country-picker__search { width: 100%; min-height: 48px; padding: 11px 13px; border: 1px solid #dbe3f2; border-radius: 6px; background: #eef2ff; color: #091e41; font: inherit; box-sizing: border-box; }
        .profile-dialog__field textarea { min-height: 100px; }
        .profile-dialog__error { display: block; color: #a62031; font-size: 12px; margin-top: 5px; }
        .profile-dialog__actions { display: flex; align-items: center; gap: 14px; margin-top: 24px; }
        .profile-dialog__actions button { border: 0; border-radius: 7px; min-height: 45px; padding: 10px 17px; font: 700 14px "Avenir Next", "Trebuchet MS", sans-serif; cursor: pointer; }
        .profile-dialog__save { background: #071f47; color: #fff; }
        .profile-dialog__skip { background: #eef2ff; color: #091e41; }
        .profile-dialog__footnote { margin-top: 17px !important; margin-bottom: 0 !important; font-size: 12px; }
        @media (max-width: 560px) { .profile-dialog { padding: 22px 18px; } .profile-dialog__fields { grid-template-columns: 1fr; } .profile-dialog__field--wide { grid-column: auto; } }
    </style>
    <div class="profile-overlay">
        <section class="profile-dialog" role="dialog" aria-modal="true" aria-labelledby="profile-dialog-heading" aria-describedby="profile-dialog-description">
            <h2 id="profile-dialog-heading">Thank you for subscribing to {{ $form->organisation?->name }} updates.</h2>
            <p id="profile-dialog-description">Complete your reader profile. These fields are optional.</p>
            <form method="POST" action="{{ route('product-forms.public.profile', ['form' => $form->slug], false) }}">
                @csrf
                <div class="profile-dialog__fields">
                    @foreach($form->field_definitions ?? [] as $field)
                        @continue($field['handle'] === 'email' || $field['type'] === 'checkbox' || $field['required'])
                        <div class="profile-dialog__field {{ in_array($field['handle'], ['first_name', 'last_name', 'firstname', 'lastname'], true) ? '' : 'profile-dialog__field--wide' }}">
                            @if($field['type'] === 'country')
                                @include('forms.public.country-picker', [
                                    'fieldId' => 'profile-'.$field['handle'], 'fieldName' => $field['handle'],
                                    'label' => $field['label'], 'required' => false,
                                    'options' => \Illuminate\Support\Facades\DB::table('countries')->orderBy('name')->get()->map(fn ($country): array => ['value' => $country->code, 'label' => $country->name]),
                                    'selected' => old($field['handle']),
                                ])
                            @else
                                <label for="profile-{{ $field['handle'] }}">{{ $field['label'] }}</label>
                                @if($field['type'] === 'select')
                                    <select id="profile-{{ $field['handle'] }}" name="{{ $field['handle'] }}">
                                        <option value="">Select an option</option>
                                        @foreach($field['options'] ?? [] as $option)
                                            <option value="{{ $option['value'] }}" @selected(old($field['handle']) === $option['value'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field['type'] === 'textarea')
                                    <textarea id="profile-{{ $field['handle'] }}" name="{{ $field['handle'] }}">{{ old($field['handle']) }}</textarea>
                                @else
                                    <input id="profile-{{ $field['handle'] }}" name="{{ $field['handle'] }}" value="{{ old($field['handle']) }}" @if(in_array($field['handle'], ['first_name', 'firstname'], true)) autocomplete="given-name" @elseif(in_array($field['handle'], ['last_name', 'lastname'], true)) autocomplete="family-name" @endif>
                                @endif
                            @endif
                            @error($field['handle'])<span class="profile-dialog__error" role="alert">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="profile-dialog__actions">
                    <button class="profile-dialog__save" type="submit" name="action" value="save">Save details</button>
                    <button class="profile-dialog__skip" type="submit" name="action" value="skip" formnovalidate>Skip for now</button>
                </div>
            </form>
            <p class="profile-dialog__footnote">You can adjust your preferences or unsubscribe anytime.</p>
        </section>
    </div>
    <script>document.querySelector('.profile-dialog input, .profile-dialog select, .profile-dialog button')?.focus();</script>
@endif
