@once
@push('head')
<style>
    .form-platform-header {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }

    .form-platform-title {
        font-size: 1.875rem;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    .form-platform-description,
    .form-platform-meta,
    .form-platform-help {
        color: #64748b;
        font-size: .875rem;
        margin: .35rem 0 0;
    }

    .form-platform-stack {
        display: grid;
        gap: 1rem;
    }

    .form-platform-card {
        background: #fff;
        border: 1px solid #d7dee8;
        border-radius: .5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        padding: 1.5rem;
    }

    .form-platform-card-flush {
        background: #fff;
        border: 1px solid #d7dee8;
        border-radius: .5rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
        overflow-x: auto;
    }

    .form-platform-row {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
    }

    .form-platform-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: flex-end;
    }

    .form-platform-button,
    .form-platform-button-primary {
        align-items: center;
        border-radius: .375rem;
        display: inline-flex;
        font-size: .875rem;
        font-weight: 600;
        justify-content: center;
        line-height: 1.25;
        min-height: 2.25rem;
        padding: .5rem .875rem;
        text-decoration: none;
    }

    .form-platform-button {
        background: #fff;
        border: 1px solid #cbd5e1;
        color: #334155;
    }

    .form-platform-button:hover {
        background: #f8fafc;
        color: #0f172a;
        text-decoration: none;
    }

    .form-platform-button-primary {
        background: #111827;
        border: 1px solid #111827;
        color: #fff;
    }

    .form-platform-button-primary:hover {
        background: #374151;
        border-color: #374151;
        color: #fff;
        text-decoration: none;
    }

    .form-platform-grid {
        display: grid;
        gap: 1.25rem 1.5rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .form-platform-field {
        min-width: 0;
    }

    .form-platform-field[hidden],
    .form-platform-grid[hidden] {
        display: none !important;
    }

    .form-platform-label {
        color: #334155;
        display: block;
        font-size: .875rem;
        font-weight: 600;
        margin-bottom: .35rem;
    }

    .form-platform-control {
        appearance: none;
        background-color: #fff;
        border: 1px solid #c7d2e0;
        border-radius: .375rem;
        box-shadow: inset 0 1px 1px rgba(15, 23, 42, .03);
        color: #0f172a;
        display: block;
        font-size: .9375rem;
        line-height: 1.4;
        min-height: 2.5rem;
        padding: .5rem .75rem;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        width: 100%;
    }

    select.form-platform-control {
        background-image:
            linear-gradient(45deg, transparent 50%, #475569 50%),
            linear-gradient(135deg, #475569 50%, transparent 50%);
        background-position:
            calc(100% - 18px) 50%,
            calc(100% - 13px) 50%;
        background-repeat: no-repeat;
        background-size: 5px 5px, 5px 5px;
        padding-right: 2.25rem;
    }

    select.form-platform-control[multiple] {
        background-image: none;
        min-height: 8rem;
        padding-right: .75rem;
    }

    textarea.form-platform-control {
        min-height: 6rem;
        resize: vertical;
    }

    .form-platform-control:focus {
        background-color: #fff;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
        outline: none;
    }

    .form-platform-control:disabled {
        background-color: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
    }

    .form-platform-control::placeholder {
        color: #94a3b8;
    }

    .form-platform-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        font-size: .875rem;
    }

    .form-platform-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: .5rem;
        color: #b91c1c;
        margin-bottom: 1rem;
        padding: 1rem;
    }

    .form-platform-error-title {
        font-weight: 600;
        margin: 0;
    }

    .form-platform-error-list {
        font-size: .875rem;
        margin: .5rem 0 0 1.25rem;
    }

    .form-platform-empty {
        color: #64748b;
        padding: 2rem;
        text-align: center;
    }

    .form-platform-checkbox {
        align-items: center;
        color: #334155;
        display: flex;
        font-size: .875rem;
        gap: .5rem;
    }

    @media (max-width: 760px) {
        .form-platform-header,
        .form-platform-row {
            display: block;
        }

        .form-platform-actions {
            justify-content: flex-start;
            margin-top: 1rem;
        }

        .form-platform-grid {
            grid-template-columns: 1fr;
        }
}
</style>
@endpush
@endonce
