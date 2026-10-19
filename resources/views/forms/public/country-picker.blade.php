<div class="country-picker" data-country-picker>
    <label for="{{ $fieldId }}" data-country-label>{{ $label }}@if($required) * @endif</label>
    <select id="{{ $fieldId }}" name="{{ $fieldName }}" data-country-select @required($required)>
        <option value="">Select a country</option>
        @foreach($options as $option)
            <option value="{{ $option['value'] }}" @selected($selected === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <input class="country-picker__search" type="text" autocomplete="off" placeholder="Search for a country" aria-label="{{ $label }}" aria-autocomplete="list" aria-expanded="false" data-country-search hidden>
    <div class="country-picker__menu" role="listbox" data-country-results hidden></div>
</div>

@once
    <style>
        .country-picker { position: relative; }
        .country-picker--enhanced select { display: none; }
        .country-picker--enhanced .country-picker__search { display: block; }
        .country-picker__menu { position: absolute; z-index: 20; inset-inline: 0; top: 100%; max-height: 230px; overflow-y: auto; background: #fff; border: 1px solid #aebdd5; border-radius: 10px; box-shadow: 0 16px 34px rgba(23, 35, 60, .16); margin-top: 5px; padding: 5px; }
        .country-picker__menu[hidden], .country-picker__search[hidden] { display: none; }
        button.country-picker__option { display: block; width: 100%; border: 0; border-radius: 7px; padding: 10px 12px; background: transparent; color: #17233c; box-shadow: none; text-align: left; font: inherit; cursor: pointer; }
        button.country-picker__option:hover, button.country-picker__option[aria-selected="true"] { background: #e9effa; }
        button.country-picker__option:focus-visible, .country-picker__search:focus-visible { outline: 2px solid #2867cf; outline-offset: 2px; }
        .country-picker__empty { padding: 10px 12px; color: #566681; }
    </style>
    <script>
        (() => {
            const init = () => {
                document.querySelectorAll('[data-country-picker]').forEach((picker, pickerIndex) => {
                    if (picker.dataset.enhanced) return;
                    const select = picker.querySelector('[data-country-select]');
                    const search = picker.querySelector('[data-country-search]');
                    const label = picker.querySelector('[data-country-label]');
                    const menu = picker.querySelector('[data-country-results]');
                    const countries = Array.from(select.options).filter(option => option.value).map(option => ({
                        code: option.value,
                        name: option.textContent.trim(),
                    }));
                    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
                    const listId = `country-picker-list-${pickerIndex}`;
                    const wasRequired = select.required;
                    let matches = [];
                    let active = -1;

                    menu.id = listId;
                    search.id = `${select.id}-search`;
                    label.htmlFor = search.id;
                    search.setAttribute('role', 'combobox');
                    search.setAttribute('aria-controls', listId);
                    search.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
                    search.hidden = false;
                    select.required = false;
                    if (wasRequired) search.required = true;
                    picker.dataset.enhanced = '1';
                    picker.classList.add('country-picker--enhanced');

                    const syncValidity = () => {
                        search.setCustomValidity(wasRequired && !select.value ? 'Choose a country from the suggestions.' : '');
                    };
                    const close = () => {
                        menu.hidden = true;
                        search.setAttribute('aria-expanded', 'false');
                        search.removeAttribute('aria-activedescendant');
                    };
                    const setActive = index => {
                        active = index;
                        menu.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
                            option.setAttribute('aria-selected', String(optionIndex === active));
                        });
                        if (active >= 0) {
                            const option = menu.querySelectorAll('[role="option"]')[active];
                            search.setAttribute('aria-activedescendant', option.id);
                            option.scrollIntoView({ block: 'nearest' });
                        } else {
                            search.removeAttribute('aria-activedescendant');
                        }
                    };
                    const choose = country => {
                        select.value = country.code;
                        search.value = country.name;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        syncValidity();
                        close();
                    };
                    const render = query => {
                        const term = normalize(query);
                        matches = countries.filter(country =>
                            normalize(country.name).includes(term) || country.code.toLowerCase().includes(term)
                        ).slice(0, 8);
                        menu.replaceChildren();
                        active = -1;
                        if (matches.length === 0) {
                            const empty = document.createElement('div');
                            empty.className = 'country-picker__empty';
                            empty.textContent = 'No matching countries';
                            menu.append(empty);
                        } else {
                            matches.forEach((country, index) => {
                                const option = document.createElement('button');
                                option.type = 'button';
                                option.className = 'country-picker__option';
                                option.id = `${listId}-option-${index}`;
                                option.setAttribute('role', 'option');
                                option.setAttribute('aria-selected', 'false');
                                option.textContent = country.name;
                                option.addEventListener('pointerdown', event => event.preventDefault());
                                option.addEventListener('click', () => choose(country));
                                menu.append(option);
                            });
                        }
                        menu.hidden = false;
                        search.setAttribute('aria-expanded', 'true');
                    };

                    search.addEventListener('focus', () => render(search.value));
                    search.addEventListener('input', () => {
                        select.value = '';
                        syncValidity();
                        render(search.value);
                    });
                    search.addEventListener('keydown', event => {
                        if (event.key === 'Escape') { close(); return; }
                        if (event.key === 'Tab') { close(); return; }
                        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                            event.preventDefault();
                            if (menu.hidden) render(search.value);
                            if (matches.length) setActive((active + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % matches.length);
                        }
                        if (event.key === 'Enter' && !menu.hidden && matches.length) {
                            event.preventDefault();
                            choose(matches[active >= 0 ? active : 0]);
                        }
                    });
                    search.addEventListener('blur', () => setTimeout(() => {
                        if (!picker.contains(document.activeElement)) {
                            close();
                            search.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
                        }
                    }, 0));
                    document.addEventListener('pointerdown', event => { if (!picker.contains(event.target)) close(); });
                    syncValidity();
                });
            };
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
            else init();
        })();
    </script>
@endonce
