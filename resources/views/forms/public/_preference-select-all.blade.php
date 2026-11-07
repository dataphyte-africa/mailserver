<button type="button" class="preference-select-all" data-select-all-preferences aria-pressed="false">Select all</button>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-select-all-preferences]').forEach((button) => {
            const choices = button.closest('form').querySelector('[data-preference-choices]');
            const checkboxes = [...choices.querySelectorAll('input[type="checkbox"]')];
            const sync = () => {
                const allSelected = checkboxes.every((checkbox) => checkbox.checked);
                button.textContent = allSelected ? 'Clear all' : 'Select all';
                button.setAttribute('aria-pressed', String(allSelected));
            };

            button.addEventListener('click', () => {
                const shouldSelect = !checkboxes.every((checkbox) => checkbox.checked);
                checkboxes.forEach((checkbox) => {
                    if (checkbox.checked !== shouldSelect) {
                        checkbox.checked = shouldSelect;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                sync();
            });
            choices.addEventListener('change', sync);
            sync();
        });
    });
</script>
