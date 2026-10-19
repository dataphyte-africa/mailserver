<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Observer Violation Intake</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400;500;700" rel="stylesheet">
    <style>
        :root {
            --color-dataphyteblue: #2f70a8;
            --color-dataphytelightblue: #4fa6de;
            --color-dataphytered: #b52408;
            --color-bg: #f5f8fb;
            --color-surface: #ffffff;
            --color-text: #183247;
            --color-muted: #5d7283;
            --color-border: #d6e2ec;
            --color-helper: rgba(79, 166, 222, 0.10);
            --color-helper-border: rgba(79, 166, 222, 0.22);
            --color-danger-soft: rgba(181, 36, 8, 0.07);
            --color-danger-border: rgba(181, 36, 8, 0.20);
            --color-success: #1f7a52;
            --shadow-card: 0 18px 40px rgba(18, 50, 71, 0.08);
            --shadow-soft: 0 8px 20px rgba(18, 50, 71, 0.06);
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --radius-full: 999px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background:
                radial-gradient(circle at top right, rgba(79, 166, 222, 0.18), transparent 30%),
                linear-gradient(180deg, #f7fbff 0%, #f5f8fb 48%, #eef4f9 100%);
            color: var(--color-text);
            font-family: "Source Sans 3", sans-serif;
            min-height: 100vh;
            padding-bottom: calc(108px + env(safe-area-inset-bottom));
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 1rem;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(79, 166, 222, 0.18);
            box-shadow: 0 2px 12px rgba(18, 50, 71, 0.04);
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .icon-fill {
            font-variation-settings: 'FILL' 1;
        }

        .brand-icon {
            color: var(--color-dataphyteblue);
            font-size: 1.5rem;
        }

        .brand-title {
            margin: 0;
            font-family: "Montserrat", sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--color-dataphyteblue);
            letter-spacing: -0.02em;
        }

        .page {
            width: min(100%, 48.75rem);
            margin: 0 auto;
            padding: 5.25rem 1rem 0;
        }

        .hero {
            margin: 0 0 1.75rem;
        }

        .eyebrow {
            margin: 0 0 0.5rem;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--color-muted);
        }

        .hero-title {
            margin: 0 0 0.75rem;
            font-family: "Montserrat", sans-serif;
            font-size: clamp(2rem, 6vw, 2.8rem);
            line-height: 1.02;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #0f2231;
        }

        .hero-copy {
            margin: 0 0 1rem;
            max-width: 42rem;
            font-size: 1rem;
            line-height: 1.55;
            color: var(--color-muted);
        }

        .trust-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 0.95rem;
            border-radius: var(--radius-full);
            background: #eef8f3;
            border: 1px solid #d5f0e0;
            color: var(--color-success);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .progress-shell {
            background: var(--color-surface);
            border: 1px solid rgba(214, 226, 236, 0.75);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 1rem 1rem 0.95rem;
            margin-bottom: 1.5rem;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.7rem;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .progress-meta-left {
            color: var(--color-muted);
        }

        .progress-meta-right {
            color: #0f2231;
        }

        .progress-track {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
            margin-bottom: 0.55rem;
        }

        .progress-bar {
            position: relative;
            height: 0.45rem;
            border-radius: var(--radius-full);
            background: #e8eef4;
            overflow: hidden;
        }

        .progress-bar[data-state="done"] {
            background: var(--color-dataphyteblue);
        }

        .progress-bar[data-state="active"]::before {
            content: "";
            position: absolute;
            inset: 0;
            width: 58%;
            background: var(--color-dataphyteblue);
            border-radius: inherit;
        }

        .progress-labels {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
        }

        .progress-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #a1afbb;
        }

        .progress-label.is-current,
        .progress-label.is-done {
            color: var(--color-dataphyteblue);
        }

        .step-panel {
            display: none;
        }

        .step-panel.is-active {
            display: block;
        }

        .form-card {
            background: var(--color-surface);
            border: 1px solid rgba(214, 226, 236, 0.72);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }

        .card-header,
        .card-section {
            padding: 1.3rem 1.25rem;
        }

        .card-header {
            border-bottom: 1px solid #edf2f6;
            background: rgba(247, 250, 252, 0.72);
        }

        .card-section + .card-section {
            border-top: 1px solid #edf2f6;
        }

        .card-section.alt {
            background: rgba(245, 248, 251, 0.84);
        }

        .section-heading {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-family: "Montserrat", sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #244258;
        }

        .section-heading .material-symbols-outlined {
            font-size: 1.15rem;
            color: #8aa0b1;
        }

        .helper-panel,
        .privacy-panel {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            margin-bottom: 1.1rem;
        }

        .helper-panel {
            background: var(--color-helper);
            border: 1px solid var(--color-helper-border);
        }

        .privacy-panel {
            background: var(--color-helper);
            border: 1px solid var(--color-helper-border);
        }

        .helper-panel .material-symbols-outlined,
        .privacy-panel .material-symbols-outlined {
            color: var(--color-dataphytelightblue);
            font-size: 1.05rem;
            margin-top: 0.05rem;
        }

        .helper-text,
        .privacy-copy {
            margin: 0;
            font-size: 0.82rem;
            line-height: 1.5;
            color: var(--color-muted);
        }

        .privacy-title {
            margin: 0 0 0.3rem;
            font-family: "Montserrat", sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            color: #183247;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .field-grid.single {
            grid-template-columns: 1fr;
        }

        .field-span-2 {
            grid-column: 1 / -1;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .field label,
        .checkbox-copy strong {
            font-size: 0.92rem;
            font-weight: 700;
            color: #314b5f;
        }

        .field small {
            color: var(--color-muted);
            font-size: 0.78rem;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            appearance: none;
            border: 1px solid #ccd8e3;
            border-radius: 0.95rem;
            background: #fbfdff;
            color: #142a3b;
            font: inherit;
            padding: 0.88rem 0.95rem;
            transition: border-color 160ms ease, box-shadow 160ms ease, background 160ms ease;
            box-shadow: 0 1px 2px rgba(18, 50, 71, 0.03);
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--color-dataphyteblue);
            box-shadow: 0 0 0 4px rgba(79, 166, 222, 0.18);
            background: #ffffff;
        }

        .field input[readonly] {
            background: #f4f8fb;
            color: #597083;
        }

        .field textarea {
            min-height: 8rem;
            resize: vertical;
        }

        .upload-zone {
            border: 2px dashed #cad8e5;
            border-radius: 1.25rem;
            background: #f8fbfe;
            padding: 2rem 1.25rem;
            text-align: center;
            transition: border-color 160ms ease, background 160ms ease;
        }

        .upload-zone:hover {
            border-color: var(--color-dataphytelightblue);
            background: #f2f9fe;
        }

        .upload-zone .material-symbols-outlined {
            font-size: 2.6rem;
            color: var(--color-dataphytelightblue);
        }

        .upload-zone p {
            margin: 0.45rem 0 0;
        }

        .upload-zone-title {
            font-size: 0.96rem;
            font-weight: 700;
            color: #244258;
        }

        .upload-zone-copy {
            font-size: 0.8rem;
            color: var(--color-muted);
        }

        .consent-block {
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
            border-radius: 1rem;
            padding: 1rem 1rem 1.05rem;
            background: var(--color-danger-soft);
            border: 1px solid var(--color-danger-border);
            margin-top: 1.2rem;
        }

        .consent-block input[type="checkbox"] {
            width: 1.15rem;
            height: 1.15rem;
            margin-top: 0.15rem;
            accent-color: var(--color-dataphytered);
        }

        .checkbox-copy {
            flex: 1;
        }

        .checkbox-copy p {
            margin: 0.25rem 0 0;
            color: var(--color-muted);
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .alert,
        .field-errors {
            display: none;
            margin-top: 1rem;
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .alert.is-visible,
        .field-errors.is-visible {
            display: block;
        }

        .alert.ok {
            background: #eef8f3;
            border: 1px solid #d1ecdc;
            color: var(--color-success);
        }

        .alert.err,
        .field-errors {
            background: #fff4f2;
            border: 1px solid rgba(181, 36, 8, 0.16);
            color: #9f2913;
        }

        .sticky-actions {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 45;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border-top: 1px solid rgba(214, 226, 236, 0.88);
            box-shadow: 0 -8px 30px rgba(18, 50, 71, 0.08);
            padding: 0.95rem 1rem calc(0.95rem + env(safe-area-inset-bottom));
        }

        .sticky-inner {
            width: min(100%, 48.75rem);
            margin: 0 auto;
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }

        .action-button {
            appearance: none;
            border: 0;
            border-radius: 1rem;
            min-height: 3.2rem;
            padding: 0.9rem 1rem;
            font: inherit;
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: transform 140ms ease, background 160ms ease, color 160ms ease, border-color 160ms ease;
            cursor: pointer;
        }

        .action-button:active {
            transform: scale(0.985);
        }

        .action-button.secondary {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cad7e2;
            color: #516779;
        }

        .action-button.primary {
            flex: 2;
            background: var(--color-dataphyteblue);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            box-shadow: 0 12px 24px rgba(47, 112, 168, 0.26);
        }

        .action-button.primary:hover {
            background: #265d8c;
        }

        .action-button[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .action-status {
            display: none;
        }

        .hidden-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        @media (max-width: 720px) {
            .field-grid {
                grid-template-columns: 1fr;
            }

            .field-span-2 {
                grid-column: auto;
            }

            .card-header,
            .card-section {
                padding: 1.15rem 1rem;
            }

            .sticky-inner {
                gap: 0.65rem;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-brand">
            <span class="material-symbols-outlined brand-icon icon-fill">security</span>
            <h1 class="brand-title">Observer Violation Intake</h1>
        </div>
    </header>

    <main class="page">
        <section class="hero">
            <p class="eyebrow">Civic Reporting</p>
            <h2 class="hero-title" id="hero-title">Observer Profile</h2>
            <p class="hero-copy" id="hero-copy">Please provide your official accreditation and deployment details. This ensures the integrity and traceability of your field report.</p>
            <div class="trust-pill">
                <span class="material-symbols-outlined icon-fill">lock</span>
                <span>Secure Institutional Data Handling</span>
            </div>
        </section>

        <section class="progress-shell" aria-label="Submission Progress">
            <div class="progress-meta">
                <span class="progress-meta-left" id="progress-count">Step 1 of 3</span>
                <span class="progress-meta-right" id="progress-title">Observer Profile</span>
            </div>
            <div class="progress-track">
                <div class="progress-bar" id="progress-bar-1" data-state="active"></div>
                <div class="progress-bar" id="progress-bar-2" data-state="pending"></div>
                <div class="progress-bar" id="progress-bar-3" data-state="pending"></div>
            </div>
            <div class="progress-labels">
                <span class="progress-label is-current" id="progress-label-1">Profile</span>
                <span class="progress-label" id="progress-label-2">Location</span>
                <span class="progress-label" id="progress-label-3">Evidence</span>
            </div>
        </section>

        <form id="observer-violation-form" enctype="multipart/form-data" novalidate>
            <section class="step-panel is-active" data-step="1">
                <div class="form-card">
                    <div class="card-header">
                        <h3 class="section-heading"><span class="material-symbols-outlined">account_circle</span> Personal Details</h3>
                    </div>
                    <div class="card-section">
                        <div class="field-grid">
                            <div class="field field-span-2">
                                <label for="observer_full_name">Full Name</label>
                                <input id="observer_full_name" name="observer_full_name" type="text" placeholder="As it appears on official ID" required>
                            </div>
                            <div class="field">
                                <label for="observer_id_or_deployment_code">Observer ID or Deployment Code</label>
                                <input id="observer_id_or_deployment_code" name="observer_id_or_deployment_code" type="text" placeholder="e.g. OBS-2026-014" required>
                            </div>
                            <div class="field">
                                <label for="observer_phone_number">Phone Number</label>
                                <input id="observer_phone_number" name="observer_phone_number" type="tel" placeholder="0803 000 0000" required>
                            </div>
                            <div class="field">
                                <label for="observer_email">Email Address</label>
                                <input id="observer_email" name="observer_email" type="email" placeholder="name@example.com" required>
                            </div>
                            <div class="field">
                                <label for="observer_organisation">Organisation</label>
                                <input id="observer_organisation" name="observer_organisation" type="text" placeholder="Dataphyte" required>
                            </div>
                            <div class="field">
                                <label for="observer_role">Observer Role</label>
                                <input id="observer_role" name="observer_role" type="text" placeholder="Field Observer" required>
                            </div>
                            <div class="field">
                                <label for="observer_verification_status">Verification Status</label>
                                <input id="observer_verification_status" name="observer_verification_status" type="text" value="pending verification" required>
                            </div>
                        </div>
                    </div>

                    <div class="card-section alt">
                        <div class="helper-panel">
                            <span class="material-symbols-outlined">info</span>
                            <p class="helper-text">Provide your assigned election geography exactly as issued to you. This allows the report to be matched to the correct observer deployment context.</p>
                        </div>

                        <h3 class="section-heading"><span class="material-symbols-outlined" style="color: var(--color-dataphyteblue);">location_on</span> Deployment Details</h3>

                        <div class="field-grid" style="margin-top: 1rem;">
                            <div class="field">
                                <label for="observer_assigned_state">Assigned State</label>
                                <input id="observer_assigned_state" name="observer_assigned_state" type="text" value="Osun" readonly required>
                            </div>
                            <div class="field">
                                <label for="assigned-lga">Assigned LGA</label>
                                <select id="assigned-lga" name="observer_assigned_lga" required></select>
                            </div>
                            <div class="field">
                                <label for="assigned-ward">Assigned Ward</label>
                                <select id="assigned-ward" name="observer_assigned_ward" disabled required></select>
                            </div>
                            <div class="field">
                                <label for="assigned-pu">Assigned Polling Unit</label>
                                <select id="assigned-pu" name="observer_assigned_polling_unit_code" disabled required></select>
                            </div>
                            <div class="field field-span-2">
                                <label for="assigned-pu-name">Assigned Polling Unit Name</label>
                                <input id="assigned-pu-name" name="observer_assigned_polling_unit_name" type="text" readonly required>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="step-panel" data-step="2">
                <div class="form-card">
                    <div class="card-section">
                        <h3 class="section-heading"><span class="material-symbols-outlined" style="color: var(--color-dataphytelightblue);">location_on</span> Where &amp; When</h3>
                        <div class="helper-panel" style="margin-top: 1rem;">
                            <span class="material-symbols-outlined">info</span>
                            <p class="helper-text">Please provide the exact administrative location where the incident was observed. This data is critical for mapping and later review.</p>
                        </div>

                        <div class="field-grid">
                            <div class="field">
                                <label for="incident_state">Incident State</label>
                                <input id="incident_state" name="incident_state" type="text" value="Osun" readonly required>
                            </div>
                            <div class="field">
                                <label for="incident-lga">Incident LGA</label>
                                <select id="incident-lga" name="incident_lga" required></select>
                            </div>
                            <div class="field">
                                <label for="incident-ward">Incident Ward</label>
                                <select id="incident-ward" name="incident_ward" disabled required></select>
                            </div>
                            <div class="field">
                                <label for="incident-pu">Incident Polling Unit</label>
                                <select id="incident-pu" name="incident_polling_unit_code" disabled required></select>
                            </div>
                            <div class="field field-span-2">
                                <label for="incident-pu-name">Incident Polling Unit Name</label>
                                <input id="incident-pu-name" name="incident_polling_unit_name" type="text" readonly required>
                            </div>
                            <div class="field field-span-2">
                                <label for="incident_address_or_landmark">Address or Landmark</label>
                                <input id="incident_address_or_landmark" name="incident_address_or_landmark" type="text" placeholder="Nearest road, school, junction, or public landmark" required>
                            </div>
                            <div class="field">
                                <label for="incident_date">Date of Incident</label>
                                <input id="incident_date" name="incident_date" type="date" required>
                            </div>
                            <div class="field">
                                <label for="incident_time_observed">Time Observed</label>
                                <input id="incident_time_observed" name="incident_time_observed" type="time" required>
                            </div>
                            <div class="field">
                                <label for="incident_gps_latitude">GPS Latitude</label>
                                <input id="incident_gps_latitude" name="incident_gps_latitude" type="text" inputmode="decimal" placeholder="Optional">
                            </div>
                            <div class="field">
                                <label for="incident_gps_longitude">GPS Longitude</label>
                                <input id="incident_gps_longitude" name="incident_gps_longitude" type="text" inputmode="decimal" placeholder="Optional">
                            </div>
                            <div class="field field-span-2">
                                <label for="incident_is_ongoing">Is the Incident Still Ongoing?</label>
                                <select id="incident_is_ongoing" name="incident_is_ongoing" required>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-section alt">
                        <h3 class="section-heading"><span class="material-symbols-outlined" style="color: var(--color-dataphytered);">warning</span> What Happened</h3>
                        <div class="field-grid single" style="margin-top: 1rem;">
                            <div class="field">
                                <label for="violation_category">Violation Category</label>
                                <input id="violation_category" name="violation_category" type="text" value="{{ $violationCategory }}" readonly required>
                            </div>
                            <div class="field">
                                <label for="incident_description">Incident Description</label>
                                <small>Provide a factual account of what happened, who was involved, and what observers saw directly.</small>
                                <textarea id="incident_description" name="incident_description" placeholder="Describe the incident clearly and objectively..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="step-panel" data-step="3">
                <div class="progress-shell" style="display: none;"></div>
                <div class="form-card">
                    <div class="card-section">
                        <div class="privacy-panel">
                            <span class="material-symbols-outlined icon-fill">shield_locked</span>
                            <div>
                                <h3 class="privacy-title">Secure Transmission</h3>
                                <p class="privacy-copy">All evidence uploaded is handled through a private storage boundary. Access is restricted to authorised Dataphyte personnel only.</p>
                            </div>
                        </div>

                        <h3 class="section-heading"><span class="material-symbols-outlined">perm_media</span> Attachments</h3>

                        <div class="field-grid single" style="margin-top: 1rem;">
                            <div class="field">
                                <label for="evidence_files">Evidence Files</label>
                                <div class="upload-zone">
                                    <span class="material-symbols-outlined">cloud_upload</span>
                                    <p class="upload-zone-title">Tap to upload or browse media</p>
                                    <p class="upload-zone-copy">Photos, videos, audio, or documents can be attached for secure review.</p>
                                </div>
                                <input id="evidence_files" name="evidence_files[]" type="file" multiple>
                            </div>
                            <div class="field">
                                <label for="evidence_description">Evidence Description</label>
                                <textarea id="evidence_description" name="evidence_description" placeholder="Summarise what the attached files show."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card-section alt">
                        <h3 class="section-heading"><span class="material-symbols-outlined">edit_document</span> Statements</h3>

                        <div class="field-grid single" style="margin-top: 1rem;">
                            <div class="field">
                                <label for="witness_statement">Witness Statement</label>
                                <textarea id="witness_statement" name="witness_statement" placeholder="Add witness details or corroborating field notes if available."></textarea>
                            </div>
                            <div class="field">
                                <label for="external_references_0">External References</label>
                                <input id="external_references_0" name="external_references[]" type="url" placeholder="https://example.com/reference">
                            </div>
                        </div>

                        <div class="consent-block">
                            <input id="evidence_consent_confirmed" name="evidence_consent_confirmed" type="checkbox" value="1" required>
                            <div class="checkbox-copy">
                                <strong>I confirm this report is accurate and consent to secure processing.</strong>
                                <p>By submitting, I acknowledge that this report and any attached evidence will be used for official Dataphyte review and handled under restricted internal access.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div id="field-errors" class="field-errors"></div>
            <div id="notice" class="alert"></div>
        </form>
    </main>

    <div class="sticky-actions">
        <div class="sticky-inner">
            <button type="button" id="back-button" class="action-button secondary">Back</button>
            <button type="button" id="next-button" class="action-button primary">
                <span id="primary-button-label">Continue</span>
                <span class="material-symbols-outlined" id="primary-button-icon">arrow_forward</span>
            </button>
        </div>
    </div>

    <script>
        const config = {
            submitEndpoint: @json($submitEndpoint),
            lgasEndpoint: @json($lgasEndpoint),
            wardsEndpoint: @json($wardsEndpoint),
            pollingUnitsEndpoint: @json($pollingUnitsEndpoint),
        };

        const form = document.getElementById('observer-violation-form');
        const panels = Array.from(document.querySelectorAll('.step-panel'));
        const heroTitle = document.getElementById('hero-title');
        const heroCopy = document.getElementById('hero-copy');
        const progressCount = document.getElementById('progress-count');
        const progressTitle = document.getElementById('progress-title');
        const backButton = document.getElementById('back-button');
        const nextButton = document.getElementById('next-button');
        const primaryButtonLabel = document.getElementById('primary-button-label');
        const primaryButtonIcon = document.getElementById('primary-button-icon');
        const notice = document.getElementById('notice');
        const fieldErrors = document.getElementById('field-errors');

        const stepMeta = [
            {
                title: 'Observer Profile',
                copy: 'Please provide your official accreditation and deployment details. This ensures the integrity and traceability of your field report.',
                primaryLabel: 'Continue',
                icon: 'arrow_forward',
            },
            {
                title: 'Incident Location',
                copy: 'Record the exact location, timing, and factual description of the incident observed in the field.',
                primaryLabel: 'Continue to Evidence',
                icon: 'arrow_forward',
            },
            {
                title: 'Evidence & Consent',
                copy: 'Provide supporting files or references and confirm consent for secure internal processing before submission.',
                primaryLabel: 'Submit Report',
                icon: 'send',
            },
        ];

        let currentStep = 0;

        function setNotice(message, kind) {
            notice.className = `alert is-visible ${kind}`;
            notice.textContent = message;
        }

        function clearMessages() {
            notice.className = 'alert';
            notice.textContent = '';
            fieldErrors.className = 'field-errors';
            fieldErrors.textContent = '';
        }

        function updateProgress() {
            const step = stepMeta[currentStep];
            heroTitle.textContent = step.title;
            heroCopy.textContent = step.copy;
            progressCount.textContent = `Step ${currentStep + 1} of 3`;
            progressTitle.textContent = step.title;
            primaryButtonLabel.textContent = step.primaryLabel;
            primaryButtonIcon.textContent = step.icon;
            backButton.style.visibility = currentStep === 0 ? 'hidden' : 'visible';

            panels.forEach((panel, index) => {
                panel.classList.toggle('is-active', index === currentStep);
            });

            for (let i = 1; i <= 3; i += 1) {
                const bar = document.getElementById(`progress-bar-${i}`);
                const label = document.getElementById(`progress-label-${i}`);

                bar.dataset.state = i - 1 < currentStep ? 'done' : (i - 1 === currentStep ? 'active' : 'pending');
                label.classList.toggle('is-done', i - 1 < currentStep);
                label.classList.toggle('is-current', i - 1 === currentStep);
            }
        }

        function fieldsForStep(stepIndex) {
            return panels[stepIndex].querySelectorAll('input, select, textarea');
        }

        function validateStep(stepIndex) {
            const controls = Array.from(fieldsForStep(stepIndex));

            for (const control of controls) {
                if (control.disabled) {
                    continue;
                }

                if (!control.checkValidity()) {
                    control.reportValidity();
                    return false;
                }
            }

            return true;
        }

        async function fetchJson(url) {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Request failed.');
            }

            return payload;
        }

        function fillOptions(select, items, placeholder, valueKey = null, labelKey = null) {
            select.innerHTML = '';
            const baseOption = document.createElement('option');
            baseOption.value = '';
            baseOption.textContent = placeholder;
            select.appendChild(baseOption);

            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = valueKey ? item[valueKey] : item;
                option.textContent = labelKey ? item[labelKey] : item;

                if (labelKey && valueKey && item.polling_unit_name) {
                    option.dataset.name = item.polling_unit_name;
                }

                select.appendChild(option);
            });

            select.disabled = false;
        }

        function resetSelect(select, placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            select.disabled = true;
        }

        async function loadLgas() {
            const payload = await fetchJson(config.lgasEndpoint);
            fillOptions(document.getElementById('assigned-lga'), payload.data, 'Select assigned LGA');
            fillOptions(document.getElementById('incident-lga'), payload.data, 'Select incident LGA');
        }

        async function loadWards(lga, target) {
            const payload = await fetchJson(`${config.wardsEndpoint}?lga=${encodeURIComponent(lga)}`);
            fillOptions(target, payload.data, 'Select ward');
        }

        async function loadPollingUnits(lga, ward, target) {
            const payload = await fetchJson(`${config.pollingUnitsEndpoint}?lga=${encodeURIComponent(lga)}&ward=${encodeURIComponent(ward)}`);
            fillOptions(target, payload.data, 'Select polling unit', 'polling_unit_code', 'polling_unit_name');
        }

        function bindLocationChain(prefix) {
            const lga = document.getElementById(`${prefix}-lga`);
            const ward = document.getElementById(`${prefix}-ward`);
            const pu = document.getElementById(`${prefix}-pu`);
            const puName = document.getElementById(`${prefix}-pu-name`);

            lga.addEventListener('change', async () => {
                resetSelect(ward, 'Loading wards...');
                resetSelect(pu, 'Select polling unit');
                puName.value = '';

                if (!lga.value) {
                    resetSelect(ward, 'Select ward');
                    return;
                }

                await loadWards(lga.value, ward);
            });

            ward.addEventListener('change', async () => {
                resetSelect(pu, 'Loading polling units...');
                puName.value = '';

                if (!ward.value || !lga.value) {
                    resetSelect(pu, 'Select polling unit');
                    return;
                }

                await loadPollingUnits(lga.value, ward.value, pu);
            });

            pu.addEventListener('change', () => {
                const selected = pu.options[pu.selectedIndex];
                puName.value = selected?.dataset?.name || '';
            });
        }

        function stepForward() {
            if (!validateStep(currentStep)) {
                return;
            }

            if (currentStep < panels.length - 1) {
                currentStep += 1;
                clearMessages();
                updateProgress();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            submitForm();
        }

        function stepBack() {
            if (currentStep === 0) {
                return;
            }

            currentStep -= 1;
            clearMessages();
            updateProgress();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function submitForm() {
            clearMessages();
            nextButton.disabled = true;
            backButton.disabled = true;
            primaryButtonLabel.textContent = 'Submitting...';
            primaryButtonIcon.textContent = 'hourglass_top';

            try {
                const response = await fetch(config.submitEndpoint, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { Accept: 'application/json' },
                });

                const payload = await response.json();

                if (!response.ok) {
                    const errors = payload.errors || {};
                    const summary = Object.entries(errors)
                        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
                        .join('\n');

                    fieldErrors.className = 'field-errors is-visible';
                    fieldErrors.textContent = summary;
                    throw new Error(payload.message || 'Submission failed.');
                }

                setNotice(`Submitted. Report ID: ${payload.report_id}. Status: ${payload.status}.`, 'ok');
                form.reset();
                document.getElementById('assigned-pu-name').value = '';
                document.getElementById('incident-pu-name').value = '';
                resetSelect(document.getElementById('assigned-ward'), 'Select ward');
                resetSelect(document.getElementById('assigned-pu'), 'Select polling unit');
                resetSelect(document.getElementById('incident-ward'), 'Select ward');
                resetSelect(document.getElementById('incident-pu'), 'Select polling unit');
                currentStep = 0;
                updateProgress();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                setNotice(error.message || 'Unable to submit report.', 'err');
            } finally {
                nextButton.disabled = false;
                backButton.disabled = false;
                updateProgress();
            }
        }

        backButton.addEventListener('click', stepBack);
        nextButton.addEventListener('click', stepForward);

        bindLocationChain('assigned');
        bindLocationChain('incident');
        updateProgress();

        loadLgas().catch((error) => {
            setNotice(error.message || 'Unable to load Osun polling-unit mapping.', 'err');
        });
    </script>
</body>
</html>
