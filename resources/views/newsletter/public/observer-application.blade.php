<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Call for Election Observers - Dataphyte Foundation</title>
    @include('partials.google-tag')
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700;800&family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #004867;
            --primary-strong: #0d5f87;
            --primary-soft: #95cdf4;
            --primary-surface: #c7e7ff;
            --ink: #1d1b16;
            --muted: #5f6368;
            --surface: #fff8f0;
            --surface-low: #f9f3ea;
            --surface-card: #ffffff;
            --line: #d9dde2;
            --line-soft: #e8e4d9;
            --error: #b42318;
            --error-bg: #fef3f2;
            --success: #027a48;
            --success-bg: #ecfdf3;
            --warning: #8a6116;
            --warning-bg: #fff9db;
            --shadow: 0 18px 50px rgba(13, 27, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #ffffff;
            color: var(--ink);
            font-family: "Work Sans", sans-serif;
        }

        img {
            max-width: 100%;
            display: block;
        }

        .nav-shell {
            width: 100%;
            padding: 0;
        }

        .nav-inner {
            width: 100%;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 28px;
            background: #ffffff;
            border-bottom: 1px solid var(--line-soft);
        }

        .page {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 0 64px;
        }

        .topbar-logo {
            width: 118px;
            max-width: 100%;
        }

        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #004867 0%, #1e668a 52%, #2a759b 100%);
            color: #ffffff;
            margin: 0 0 26px;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.08);
            transform: rotate(12deg);
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.06) 0, rgba(255, 255, 255, 0) 28%),
                linear-gradient(0deg, rgba(255, 255, 255, 0.04) 0, rgba(255, 255, 255, 0) 42%);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
            max-width: 1040px;
            margin: 0 auto;
            padding: 34px 28px 30px;
        }

        .hero-title {
            margin: 0 0 14px;
            font-family: "Hanken Grotesk", sans-serif;
            font-size: clamp(20px, 2.2vw, 32px);
            line-height: 1.02;
            letter-spacing: -0.03em;
            font-weight: 800;
            max-width: 820px;
        }

        .hero-copy {
            margin: 0;
            max-width: 760px;
            font-size: clamp(16px, 2vw, 18px);
            line-height: 1.62;
            color: rgba(255, 255, 255, 0.9);
        }

        .hero-info {
            max-width: 1040px;
            margin: 0 auto 25px;
            padding: 0 28px 5px 28px;
            display: grid;
            gap: 0;
            border-bottom: solid 1px #e5e5e5;
        }

        .hero-info-inner {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
            padding: 8px 0 0;
        }

        .hero-info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding-right: 0;
        }

        .hero-info-row .material-symbols-outlined {
            font-size: 20px;
            color: var(--primary);
            flex: 0 0 auto;
            margin-top: 3px;
        }

        .hero-info-label {
            display: block;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        .hero-info-row p {
            margin: 0;
            font-size: 12px;
            line-height: 1.58;
            color: #54606c;
        }

        .shell {
            max-width: 1040px;
            margin: 0 auto;
            background: transparent;
            border: 0;
            padding: 0 28px 32px;
            box-shadow: none;
        }

        .notice {
            display: none;
            margin-bottom: 20px;
            padding: 16px 18px;
            border-radius: 14px;
            font-size: 15px;
            line-height: 1.6;
        }

        .notice.show {
            display: block;
        }

        .notice.error {
            background: var(--error-bg);
            color: var(--error);
            border: 1px solid #fecdca;
        }

        .notice.success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid #abefc6;
        }

        .form-stack {
            display: grid;
            gap: 18px;
        }

        fieldset {
            margin: 0;
            border: 0;
            min-width: 0;
        }

        .form-section {
            padding: 18px;
            border: 1px solid #f1ede5;
            border-radius: 14px;
            background: #ffffff;
        }

        .section-legend {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .section-box {
            padding: 18px;
            border-radius: 14px;
            background: #fcfaf5;
            border: 1px solid #f1ede5;
        }

        .grid {
            display: grid;
            gap: 14px;
        }

        .grid.two {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label,
        .field .label {
            display: block;
            font-size: 14px;
            line-height: 1.4;
            font-weight: 600;
            color: var(--ink);
        }

        .input {
            width: 100%;
            border: 1px solid #c0c7ce;
            border-radius: 10px;
            padding: 13px 14px;
            font-size: 16px;
            line-height: 1.4;
            font-family: inherit;
            color: var(--ink);
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 72, 103, 0.08);
        }

        .choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .choice-tile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 15px;
            border: 1px solid var(--line-soft);
            border-radius: 10px;
            background: #ffffff;
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .choice-tile:has(input:checked) {
            border-color: var(--primary);
            background: rgba(0, 72, 103, 0.04);
            box-shadow: 0 0 0 2px rgba(0, 72, 103, 0.05);
        }

        .choice-tile input {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            flex: 0 0 auto;
        }

        .choice-tile span {
            font-size: 15px;
            line-height: 1.4;
        }

        .toggle-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 15px;
            border: 1px solid var(--line-soft);
            border-radius: 10px;
            background: #ffffff;
        }

        .toggle-card input {
            margin-top: 2px;
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            flex: 0 0 auto;
        }

        .toggle-card strong {
            display: block;
            margin-bottom: 4px;
            font-size: 16px;
            line-height: 1.45;
        }

        .toggle-card p {
            margin: 0;
            font-size: 14px;
            line-height: 1.55;
            color: #5f6368;
        }

        .inline-note {
            display: none;
            margin-top: 10px;
            padding: 13px 14px;
            border-radius: 10px;
            background: var(--warning-bg);
            border: 1px solid #fce588;
            color: var(--warning);
            font-size: 14px;
            line-height: 1.55;
        }

        .inline-note.show {
            display: block;
        }

        .inline-note.soft-error {
            background: #fef3f2;
            border-color: #fecdca;
            color: var(--error);
        }

        .verify-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px;
            border-radius: 12px;
            background: rgba(149, 205, 244, 0.18);
            border: 1px solid rgba(149, 205, 244, 0.36);
            color: #495057;
            font-size: 14px;
            line-height: 1.55;
        }

        .verify-box .material-symbols-outlined {
            color: var(--primary);
            font-size: 22px;
            flex: 0 0 auto;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding-top: 12px;
        }

        .button {
            appearance: none;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #ffffff;
            padding: 15px 28px;
            min-width: 220px;
            font-size: 18px;
            line-height: 1.2;
            font-weight: 700;
            font-family: "Work Sans", sans-serif;
            cursor: pointer;
            box-shadow: 0 16px 30px rgba(0, 72, 103, 0.16);
            transition: transform 0.15s ease, background-color 0.15s ease, opacity 0.15s ease;
        }

        .button:hover {
            background: var(--primary-strong);
        }

        .button:active {
            transform: scale(0.985);
        }

        .button[disabled] {
            opacity: 0.6;
            cursor: wait;
        }

        .helper {
            color: #5f6368;
            font-size: 14px;
            line-height: 1.6;
        }

        .site-footer {
            width: 100%;
            margin-top: 0;
            border-top: 1px solid var(--line-soft);
            background: #ffffff;
        }

        .site-footer-inner {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 12px 28px;
            color: #6c737c;
            font-size: 12px;
            line-height: 1.5;
        }

        .site-footer-logo {
            width: 124px;
        }

        @media (max-width: 820px) {
            .nav-shell {
                padding-top: 0;
            }

            .nav-inner {
                min-height: 56px;
                padding: 10px 16px;
            }

            .page {
                padding-bottom: 44px;
            }

            .hero-inner,
            .shell {
                padding-left: 16px;
                padding-right: 16px;
            }

            .hero-inner {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .site-footer-inner,
            .actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero {
                margin-bottom: 20px;
            }

            .hero-title {
                font-size: 22px;
                line-height: 1.04;
            }

            .hero-copy {
                font-size: 16px;
                line-height: 1.6;
            }

            .hero-info {
                margin-bottom: 16px;
                padding: 0 16px;
            }

            .hero-info-inner {
                grid-template-columns: 1fr;
                gap: 16px;
                padding-top: 2px;
            }

            .hero-info-row {
                padding-right: 0;
            }

            .grid.two {
                grid-template-columns: 1fr;
            }

            .choice-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .choice-tile {
                padding: 12px 14px;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="nav-shell">
        <div class="nav-inner">
            <img class="topbar-logo" src="{{ $foundationLogoUrl }}" alt="Dataphyte Foundation logo">
        </div>
    </header>

    <main class="page">
        <section class="hero">
            <div class="hero-inner">
                <h1 class="hero-title">Call for Election Observers for Osun State Governorship Election</h1>
                <p class="hero-copy">
                    Are you a citizen or resident of Osun State? Are you interested in strengthening electoral participation and democratic governance?
                </p>
                <p class="hero-copy" style="margin-top:14px;">
                    Fill this form carefully to register your interest as an observer for the Osun State Governorship Election scheduled for
                    <strong>Saturday, August 15, 2026</strong>.
                </p>
            </div>
        </section>

        <div class="hero-info">
            <div class="hero-info-inner">
                <div class="hero-info-row">
                    <span class="material-symbols-outlined">task_alt</span>
                    <div>
                        <span class="hero-info-label">What You Will Do</span>
                        <p>As an observer, you will observe electoral processes in your respective local governments on the day of the election, document the entire election process, incidents, voting, and result collation.</p>
                    </div>
                </div>

                <div class="hero-info-row">
                    <span class="material-symbols-outlined">event_busy</span>
                    <div>
                        <span class="hero-info-label">Deadline</span>
                        <p>11:59 PM. on Friday, July 24, 2026.</p>
                    </div>
                </div>

                <div class="hero-info-row">
                    <span class="material-symbols-outlined">info</span>
                    <div>
                        <span class="hero-info-label">Important Note</span>
                        <p>This application does not automatically guarantee selection. Status updates will be sent within a week after the deadline.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="shell">
            <div id="formNotice" class="notice"></div>

            <form id="observerForm" novalidate>
                <div class="form-stack">
                    <fieldset class="form-section">
                        <legend class="section-legend">Personal Information</legend>
                        <div class="grid">
                            <div class="field full">
                                <label for="full_name">Full Name</label>
                                <input class="input" id="full_name" name="full_name" placeholder="Enter your full legal name" type="text" autocomplete="name" required>
                            </div>

                            <div class="grid two">
                                <div class="field">
                                    <label for="phone_number">Phone Number (WhatsApp preferably)</label>
                                    <input class="input" id="phone_number" name="phone_number" placeholder="e.g. +234 800 000 0000" type="tel" inputmode="tel" required>
                                </div>
                                <div class="field">
                                    <label for="email">Email Address</label>
                                    <input class="input" id="email" name="email" placeholder="email@example.com" type="email" autocomplete="email" pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$" title="Enter a valid email address." required>
                                    <div id="emailNote" class="inline-note soft-error">Enter a valid email address before submitting.</div>
                                </div>
                            </div>

                            <div class="grid two">
                                <div class="field">
                                    <label for="gender">Gender</label>
                                    <select class="input" id="gender" name="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label for="age">Age</label>
                                    <input class="input" id="age" min="18" name="age" placeholder="Enter your age" type="number" inputmode="numeric" required>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section section-box">
                        <legend class="section-legend">Eligibility Confirmation</legend>
                        <label class="toggle-card">
                            <input id="confirm_above_18" name="confirm_above_18" type="checkbox" value="1" required>
                            <span>
                                <strong>I confirm that I am 18 years or older.</strong>
                                <p>You must confirm this before continuing with the application.</p>
                            </span>
                        </label>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="section-legend">Residency &amp; Location</legend>
                        <div class="grid">
                            <div class="field full">
                                <span class="label">Are you currently resident in Osun State?</span>
                                <div class="choice-grid">
                                    <label class="choice-tile">
                                        <input name="resident_in_osun" type="radio" value="yes" required>
                                        <span>Yes</span>
                                    </label>
                                    <label class="choice-tile">
                                        <input name="resident_in_osun" type="radio" value="no" required>
                                        <span>No</span>
                                    </label>
                                </div>
                                <div id="residentNote" class="inline-note">{{ $ineligibleMessage }}</div>
                            </div>

                            <div class="grid two">
                                <div class="field">
                                    <label for="lga_id">Local Government Area of Residence</label>
                                    <select class="input" id="lga_id" name="lga_id" required disabled>
                                        <option value="">Loading Osun LGAs...</option>
                                    </select>
                                    <input id="lga_name" name="lga_name" type="hidden">
                                </div>
                                <div class="field">
                                    <label for="ward_id">Electoral Ward of Residence</label>
                                    <select class="input" id="ward_id" name="ward_id" required disabled>
                                        <option value="">Select an LGA first</option>
                                    </select>
                                    <input id="ward_name" name="ward_name" type="hidden">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="section-legend">Experience &amp; Availability</legend>
                        <div class="grid">
                            <div class="field full">
                                <span class="label">Do you have experience in election observation?</span>
                                <div class="choice-grid">
                                    <label class="choice-tile">
                                        <input name="has_election_observation_experience" type="radio" value="yes" required>
                                        <span>Yes</span>
                                    </label>
                                    <label class="choice-tile">
                                        <input name="has_election_observation_experience" type="radio" value="no" required>
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="field full">
                                <span class="label">Will you be available on Saturday, August 15, 2026 to observe the Osun Governorship election?</span>
                                <div class="choice-grid">
                                    <label class="choice-tile">
                                        <input name="available_for_election_day" type="radio" value="yes" required>
                                        <span>Yes</span>
                                    </label>
                                    <label class="choice-tile">
                                        <input name="available_for_election_day" type="radio" value="no" required>
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>

                            <div class="field full">
                                <span class="label">Selected applicants will be required to participate in a mandatory training session. Will you be available for the training?</span>
                                <div class="choice-grid">
                                    <label class="choice-tile">
                                        <input name="available_for_training" type="radio" value="yes" required>
                                        <span>Yes</span>
                                    </label>
                                    <label class="choice-tile">
                                        <input name="available_for_training" type="radio" value="no" required>
                                        <span>No</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="section-legend">Security &amp; Contact</legend>
                        <div class="grid">
                            <div class="field full">
                                <label for="emergency_phone_number">Emergency Phone Number (for security reasons)</label>
                                <input class="input" id="emergency_phone_number" name="emergency_phone_number" placeholder="Contact person's number" type="tel" inputmode="tel" required>
                            </div>

                            <div class="field full">
                                <label class="toggle-card">
                                    <input id="future_foundation_updates" name="future_foundation_updates" type="checkbox" value="1" required>
                                    <span>
                                        <strong>I would like to receive future communication from Dataphyte Foundation on data collections and related foundation updates.</strong>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="section-legend">Verification</legend>
                        @if($turnstileBypass)
                        <div class="verify-box">
                            <span class="material-symbols-outlined">verified_user</span>
                            <span>Turnstile bypass is active on this non-production environment. Test submissions can proceed without verification.</span>
                        </div>
                        @elseif($turnstileSiteKey !== '')
                        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-callback="onTurnstileSuccess"></div>
                        @else
                        <div class="verify-box">
                            <span class="material-symbols-outlined">verified_user</span>
                            <span>Cloudflare Turnstile is not configured yet on this environment. Add the site key before public testing.</span>
                        </div>
                        @endif
                        <input id="turnstile_token" name="turnstile_token" type="hidden" required>
                    </fieldset>

                    <div class="actions">
                        <button id="submitButton" class="button" type="submit">Submit application</button>
                        <p class="helper">This form stores one application per email address and phone number.</p>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <img class="site-footer-logo" src="{{ $foundationLogoUrl }}" alt="Dataphyte Foundation logo">
            <div>&copy; {{ now()->year }} Dataphyte Foundation. All rights reserved.</div>
        </div>
    </footer>

    @if($turnstileSiteKey !== '' && ! $turnstileBypass)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    <script>
        window.onTurnstileSuccess = function(token) {
            document.getElementById('turnstile_token').value = token;
        };

        const schemaEndpoint = @json($schemaEndpoint);
        const submitEndpoint = @json($submitEndpoint);
        const statesEndpoint = @json($statesEndpoint);
        const lgasEndpointTemplate = @json($lgasEndpointTemplate);
        const wardsEndpointTemplate = @json($wardsEndpointTemplate);
        let osunState = @json($osunState);
        const closedAt = @json($closedAtIso);
        const closedMessage = @json($closedMessage);
        const successMessage = @json($successMessage);
        const ineligibleMessage = @json($ineligibleMessage);
        const turnstileBypass = @json($turnstileBypass);

        const form = document.getElementById('observerForm');
        const notice = document.getElementById('formNotice');
        const residentNote = document.getElementById('residentNote');
        const submitButton = document.getElementById('submitButton');
        const emailInput = document.getElementById('email');
        const emailNote = document.getElementById('emailNote');
        const lgaSelect = document.getElementById('lga_id');
        const wardSelect = document.getElementById('ward_id');
        const lgaNameInput = document.getElementById('lga_name');
        const wardNameInput = document.getElementById('ward_name');
        const turnstileTokenInput = document.getElementById('turnstile_token');
        const storagePrefix = 'foundation.osun-election-observers';

        const setNotice = (message, type = 'error') => {
            notice.textContent = message;
            notice.className = `notice ${type} show`;
            notice.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        };

        const clearNotice = () => {
            notice.textContent = '';
            notice.className = 'notice';
        };

        const validateEmailField = ({ showEmptyError = false } = {}) => {
            const value = String(emailInput.value || '').trim();

            if (!value) {
                emailInput.setCustomValidity(showEmptyError ? 'Email address is required.' : '');
                emailNote.classList.toggle('show', showEmptyError);
                return !showEmptyError;
            }

            // Clear any previous custom error before relying on the browser email validator.
            emailInput.setCustomValidity('');

            const valid = emailInput.checkValidity();

            if (!valid && emailInput.validity.typeMismatch) {
                emailInput.setCustomValidity('Enter a valid email address.');
            }

            emailNote.classList.toggle('show', !valid);

            return valid;
        };

        const readCache = (key) => {
            try {
                const raw = localStorage.getItem(`${storagePrefix}.${key}`);
                return raw ? JSON.parse(raw) : null;
            } catch (error) {
                return null;
            }
        };

        const writeCache = (key, value) => {
            try {
                localStorage.setItem(`${storagePrefix}.${key}`, JSON.stringify({
                    saved_at: new Date().toISOString(),
                    data: value,
                }));
            } catch (error) {
                // Ignore storage write failures.
            }
        };

        const populateSelect = (select, items, placeholder) => {
            select.innerHTML = '';

            const first = document.createElement('option');
            first.value = '';
            first.textContent = placeholder;
            select.appendChild(first);

            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                option.dataset.name = item.name;
                select.appendChild(option);
            });

            select.disabled = false;
        };

        const getJson = async (url) => {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json'
                },
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            return response.json();
        };

        const loadStates = async () => {
            const cached = readCache('states');

            if (cached?.data?.length) {
                const fromCache = cached.data.find((state) => String(state.name).toLowerCase() === 'osun');
                if (fromCache && (!osunState || !osunState.id)) {
                    osunState = fromCache;
                }
            }

            const payload = await getJson(statesEndpoint);
            writeCache('states', payload.data);

            const fromOrigin = payload.data.find((state) => String(state.name).toLowerCase() === 'osun');
            if (fromOrigin) {
                osunState = fromOrigin;
            }
        };

        const loadLgas = async () => {
            if (!osunState || !osunState.id) {
                throw new Error('Osun state could not be resolved from the election location API.');
            }

            const cacheKey = `lgas.${osunState.id}`;
            const cached = readCache(cacheKey);

            if (cached?.data?.length) {
                populateSelect(lgaSelect, cached.data, 'Select LGA');
            }

            const url = lgasEndpointTemplate.replace('__STATE__', String(osunState.id));
            const payload = await getJson(url);

            writeCache(cacheKey, payload.data);
            populateSelect(lgaSelect, payload.data, 'Select LGA');
        };

        const loadWards = async (lgaId) => {
            const cacheKey = `wards.${lgaId}`;
            const cached = readCache(cacheKey);

            if (cached?.data?.length) {
                populateSelect(wardSelect, cached.data, 'Select ward');
            } else {
                wardSelect.innerHTML = '<option value="">Loading wards...</option>';
                wardSelect.disabled = true;
            }

            const url = wardsEndpointTemplate.replace('__LGA__', String(lgaId));
            const payload = await getJson(url);

            writeCache(cacheKey, payload.data);
            populateSelect(wardSelect, payload.data, 'Select ward');
        };

        const refreshSelectedNames = () => {
            lgaNameInput.value = lgaSelect.selectedOptions[0]?.dataset?.name || '';
            wardNameInput.value = wardSelect.selectedOptions[0]?.dataset?.name || '';
        };

        const formDataToObject = (formData) => {
            const payload = {};

            for (const [key, value] of formData.entries()) {
                payload[key] = value;
            }

            payload.confirm_above_18 = document.getElementById('confirm_above_18').checked ? '1' : '';
            payload.future_foundation_updates = document.getElementById('future_foundation_updates').checked ? '1' : '';
            payload.turnstile_token = turnstileTokenInput.value;

            return payload;
        };

        const validateClientSide = (payload) => {
            if (closedAt && new Date() > new Date(closedAt)) {
                return closedMessage;
            }

            if (payload.confirm_above_18 !== '1') {
                return 'You must confirm that you are 18 years or older before continuing.';
            }

            if (!payload.age || Number(payload.age) < 18) {
                return 'Applicants must be 18 years or older.';
            }

            if (payload.resident_in_osun !== 'yes') {
                return ineligibleMessage;
            }

            if (!payload.lga_id || !payload.ward_id) {
                return 'Select both your local government area and ward of residence.';
            }

            if (!turnstileBypass && !payload.turnstile_token) {
                return 'Complete the security verification before submitting.';
            }

            return null;
        };

        form.addEventListener('change', async (event) => {
            if (event.target.name === 'resident_in_osun') {
                const isResident = event.target.value === 'yes';
                residentNote.classList.toggle('show', !isResident);

                if (!isResident) {
                    setNotice(ineligibleMessage, 'error');
                } else {
                    clearNotice();
                }
            }

            if (event.target === lgaSelect) {
                refreshSelectedNames();
                wardNameInput.value = '';

                if (lgaSelect.value) {
                    try {
                        await loadWards(lgaSelect.value);
                    } catch (error) {
                        setNotice('Unable to load wards right now. Please retry in a moment.');
                    }
                } else {
                    wardSelect.innerHTML = '<option value="">Select an LGA first</option>';
                    wardSelect.disabled = true;
                }
            }

            if (event.target === wardSelect) {
                refreshSelectedNames();
            }
        });

        emailInput.addEventListener('blur', () => {
            validateEmailField({
                showEmptyError: true
            });
        });

        emailInput.addEventListener('input', () => {
            validateEmailField({
                showEmptyError: false
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearNotice();
            refreshSelectedNames();

            if (!validateEmailField({
                    showEmptyError: true
                })) {
                setNotice(emailInput.validationMessage || 'Enter a valid email address before submitting.', 'error');
                return;
            }

            const payload = formDataToObject(new FormData(form));
            const validationError = validateClientSide(payload);

            if (validationError) {
                setNotice(validationError, 'error');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            try {
                const response = await fetch(submitEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    const firstError = result.errors ? Object.values(result.errors).flat()[0] : null;
                    throw new Error(firstError || result.message || 'Unable to submit your application right now.');
                }

                form.reset();
                lgaSelect.innerHTML = '<option value="">Select LGA</option>';
                wardSelect.innerHTML = '<option value="">Select an LGA first</option>';
                wardSelect.disabled = true;
                lgaNameInput.value = '';
                wardNameInput.value = '';
                turnstileTokenInput.value = '';
                residentNote.classList.remove('show');
                emailNote.classList.remove('show');

                if (window.turnstile) {
                    window.turnstile.reset();
                }

                await loadLgas();
                setNotice(result.message || successMessage, 'success');
            } catch (error) {
                setNotice(error.message || 'Unable to submit your application right now.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Submit application';
            }
        });

        Promise.allSettled([
            getJson(schemaEndpoint),
            loadStates().then(loadLgas),
        ]).then((results) => {
            const failed = results.find((result) => result.status === 'rejected');
            if (failed) {
                setNotice('The form loaded with incomplete location data. Check your connection and refresh if you cannot see LGAs.');
            }
        });
    </script>
</body>

</html>
