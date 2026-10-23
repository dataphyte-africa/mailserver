<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>2027 State Coordinator Application | Dataphyte Foundation</title>
    <style>
        :root{--ink:#142039;--muted:#52617b;--line:#ced8e8;--accent:#2357b7;--paper:#fff;--wash:#f2f5fa;--danger:#a32635}
        *{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 90% 5%,#dce9f9 0,transparent 36%),var(--wash);color:var(--ink);font:15px/1.55 "Avenir Next","Segoe UI",sans-serif}
        .shell{max-width:720px;margin:0 auto;padding:0 0 72px}.head{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:20px;min-height:76px;padding:15px 24px;background:#fff;border-bottom:1px solid var(--line)}.logo{display:block;width:195px;max-width:100%;height:auto;object-fit:contain}.eyebrow{font-size:11px;letter-spacing:.13em;text-transform:uppercase;font-weight:800;color:var(--accent)}.head-nav{display:flex;align-items:center;justify-content:flex-end;gap:3px;min-width:0;overflow-x:auto;overscroll-behavior-inline:contain;scroll-snap-type:x proximity;scrollbar-width:none}.head-nav[hidden],.entry-panel[hidden],.panel[hidden]{display:none}.head-nav::-webkit-scrollbar{display:none}.head-nav button{flex:none;scroll-snap-align:center;white-space:nowrap;border:0;border-radius:7px;background:transparent;color:var(--muted);padding:7px 8px;font-size:12px;font-weight:600;line-height:1.2}.head-nav button:hover{background:#f4f7fc;color:var(--ink);filter:none}.head-nav button.current,.head-nav button.current:hover{background:#eaf1fb;color:var(--accent)}.head-nav button:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
        .hero{background:#4d70ad;color:#fff;border-radius:0;padding:30px 32px}.hero h1{font-size:clamp(28px,4vw,40px);line-height:1.1;letter-spacing:-.035em;margin:0}.hero p{max-width:550px;color:#f4f7ff}.hero .deadline{display:block;margin-top:18px;color:#fff;font-size:14px;font-weight:700}
        .intro{padding:10px 8px 0;margin-top:18px}.key-points{margin:0;padding-left:22px;display:grid;gap:14px}.key-points li{padding-left:4px;line-height:1.65}.key-points li::marker{color:var(--accent)}.note{font-weight:600;color:#2d4d81}.hero[hidden],.intro[hidden]{display:none}.panel{background:var(--paper);border:1px solid var(--line);border-radius:16px;padding:24px 26px;margin-top:18px;box-shadow:0 6px 24px #14294a0b}.entry-panel{margin-top:20px;padding:28px;background:#edf5ff;border:1px solid #c5d8f3;border-radius:16px;box-shadow:0 6px 24px #14294a0b}.entry-panel h2{font-size:24px;line-height:1.2;letter-spacing:-.02em;margin:0 0 8px}.entry-panel p{margin:0 0 18px;color:var(--muted)}.entry-controls{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}.entry-controls label{margin:0;flex:1;min-width:240px}.entry-controls input{font-size:17px;min-height:50px}.entry-controls button{min-height:50px}.entry-note{margin:14px 0 0!important;font-size:13px}.entry-message{min-height:20px;margin:12px 0 0!important;font-size:13px}.save-status{min-height:22px;margin:14px 0 -9px;color:var(--muted);font-size:13px}.save-status[data-state=error]{color:var(--danger)}.saved-file{display:block;color:#1d6040;font-size:12px;margin-top:5px}
        .shell>.intro,.shell>.alert,.shell>.entry-panel,.shell>.panel,.shell>.foot{margin-left:18px;margin-right:18px}.draft-expiry{margin:14px 0 0;color:var(--muted);font-size:13px}.upload-status{display:block;color:var(--muted);font-size:12px;margin-top:5px}.upload-status[data-state=failed]{color:var(--danger)}.upload-status[data-state=ready]{color:#1d6040}
        fieldset.step{border:0;padding:0;margin:0;min-width:0;scroll-margin-top:100px}fieldset.step[hidden]{display:none}legend{font-size:21px;font-weight:700;letter-spacing:-.02em;padding:0;margin-bottom:5px}.section-intro{color:var(--muted);font-size:14px;margin:0 0 20px}.grid{display:grid;grid-template-columns:1fr;gap:17px}.field{min-width:0}.field.wide{grid-column:1/-1}label,.label{font-size:14px;font-weight:600;display:block;margin-bottom:7px}.help{display:block;color:var(--muted);font-size:12px;margin-top:5px}.required{color:var(--danger)}input:not([type=radio]):not([type=checkbox]),select,textarea{width:100%;font:inherit;color:var(--ink);padding:10px 12px;border:1px solid #b7c6dc;border-radius:9px;background:#fff;min-height:44px}textarea{min-height:110px;resize:vertical}input:focus,select:focus,textarea:focus{outline:2px solid #4887ea;outline-offset:1px}.options{display:flex;gap:12px 20px;flex-wrap:wrap}.options label{font-weight:500;margin:0;display:flex;align-items:center;gap:8px}.options input{width:17px;height:17px}.question{border-top:1px solid var(--line);padding-top:20px;margin-top:5px}.error{color:var(--danger);font-size:13px;margin:5px 0 0}.alert{padding:15px 18px;border-radius:11px;margin-top:20px}.alert.error-box{background:#fff1f1;border:1px solid #ebc8cb;color:#751b29}.alert.success{background:#e9f7ef;border:1px solid #a3d5b6;color:#1d6040}.actions{display:flex;justify-content:space-between;gap:12px;margin-top:26px}button{font:inherit;cursor:pointer;border:1px solid var(--ink);background:var(--ink);color:#fff;border-radius:9px;padding:11px 20px;font-weight:600}button.secondary{background:#fff;color:var(--ink)}button:hover{filter:brightness(1.12)}button:disabled{opacity:.55;cursor:not-allowed}.foot{color:var(--muted);font-size:13px;margin:32px 0;text-align:center}
        .location-picker{position:relative}.location-picker.enhanced select{position:absolute;width:1px;height:1px;min-height:0;padding:0;opacity:0;pointer-events:none}.location-picker input[type=search]{padding-right:35px}.location-results{position:absolute;z-index:5;top:calc(100% + 4px);left:0;right:0;height:220px;max-height:45vh;overflow-y:auto;overscroll-behavior:contain;background:#fff;border:1px solid #aebed6;border-radius:9px;box-shadow:0 12px 24px #14294a24}.location-results[hidden]{display:none}.location-option{display:block;width:100%;border:0;border-radius:0;background:#fff;color:var(--ink);padding:10px 12px;text-align:left;font-weight:500}.location-option:hover,.location-option.active{background:#e9f1ff;filter:none}.location-empty{padding:11px 12px;color:var(--muted)}.terms-scroll{height:320px;overflow-y:auto;overscroll-behavior:contain;border:1px solid var(--line);border-radius:10px;background:#f8faff;padding:18px;line-height:1.55}.terms-scroll h2{font-size:17px;margin:20px 0 8px}.terms-scroll h2:first-child{margin-top:0}.terms-scroll p,.terms-scroll li{font-size:14px}.terms-scroll ul{padding-left:22px}.terms-accept{margin-top:20px;display:flex;align-items:flex-start;gap:10px;font-weight:500}.terms-accept input{margin-top:5px}.terms-accept.locked{color:var(--muted)}button:disabled{background:#8d97a9;border-color:#8d97a9;color:#fff;opacity:1;filter:none}
        @media(min-width:640px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 20px}}@media(max-width:639px){.shell{padding-bottom:48px}.head{min-height:0;align-items:flex-start;flex-direction:column;gap:10px;padding:13px 18px}.logo{width:175px}.head-nav{width:100%;justify-content:flex-start;gap:2px}.head-nav button{font-size:12px;padding:7px 9px}.head .eyebrow{text-align:left}.hero{padding:23px 20px}.intro{padding:8px 5px 0}.entry-panel,.panel{padding:20px 17px}.entry-controls button{width:100%}.shell>.intro,.shell>.alert,.shell>.entry-panel,.shell>.panel,.shell>.foot{margin-left:12px;margin-right:12px}fieldset.step{scroll-margin-top:115px}.actions button{padding:10px 14px}}
    </style>
</head>
<body>
@php
    $showApplicationForm = $draft || $errors->any();
@endphp
<main class="shell">
    <div class="head"><img class="logo" src="{{ $logoUrl }}" alt="Dataphyte Foundation">
        @if($isOpen && !session('application_received'))
            <nav class="head-nav" aria-label="Application sections" @unless($showApplicationForm) hidden @endunless>
                <button type="button" class="current" data-step-label="0" aria-current="step">Personal information</button>
                <button type="button" data-step-label="1">Experience</button>
                <button type="button" data-step-label="2">Coordination</button>
                <button type="button" data-step-label="3">Terms</button>
            </nav>
        @else
            <span class="eyebrow">2027 Election Observation</span>
        @endif
    </div>
    <header class="hero" id="application-hero">
        <h1>Call for State Coordinators</h1>
        <p>{{ $content->heroIntro() }}</p>
        <span class="deadline">The deadline for application is {{ $closesAt->format('g:i A \\o\\n l, F j, Y') }}.</span>
    </header>
    <div class="intro" id="application-intro">
        <ul class="key-points">
            <li>{{ $content->intro() }}</li>
            <li>{{ $content->selectionNote() }}</li>
            <li class="note">{{ $content->preparationNote() }}</li>
        </ul>
    </div>

    @if(session('application_received'))
        <div class="alert success" role="status">Application received. Your reference is <strong>{{ session('application_received') }}</strong>. Keep it for your records.</div>
    @elseif(! $isOpen)
        <div class="alert error-box" role="status">Applications for this call have closed.</div>
    @else
        @if($errors->any())
            <div class="alert error-box" role="alert">
                <strong>Please correct the following before submitting:</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if(session('draft_resumed'))
            <div class="alert success" role="status">Your saved application has been restored.</div>
        @endif
        @if($errors->has('resume'))
            <div class="alert error-box" role="alert">{{ $errors->first('resume') }}</div>
        @endif
        <section class="entry-panel" id="application-entry" @if($showApplicationForm) hidden @endif aria-labelledby="application-entry-title">
            <h2 id="application-entry-title">Start your application</h2>
            <p>Enter your email address to begin.</p>
            <div class="entry-controls"><label for="entry-email">Email address<input id="entry-email" type="email" autocomplete="email" inputmode="email" required></label><button type="button" id="entry-continue">Continue</button></div>
            <p id="entry-message" class="entry-message" aria-live="polite"></p>
        </section>
        <section class="entry-panel" id="pending-application" hidden aria-labelledby="pending-application-title">
            <h2 id="pending-application-title">You have a pending application</h2>
            <p>Do you want to continue from where you stopped?</p>
            <div class="entry-controls"><button type="button" id="send-resume-link">Send resume link</button><button type="button" class="secondary" id="start-again">Start again</button></div>
            <p class="entry-note">You need access to this email inbox to continue the pending application.</p>
            <p id="pending-message" class="entry-message" aria-live="polite"></p>
        </section>
        <section class="entry-panel" id="submitted-application" hidden aria-labelledby="submitted-application-title">
            <h2 id="submitted-application-title">Application already submitted</h2>
            <p>An application has already been submitted with this email address. Check your inbox for the confirmation email.</p>
        </section>
        <form class="panel" action="{{ route('focal-points.applications.store') }}" method="post" enctype="multipart/form-data" id="application-form" data-draft-step-url="{{ route('focal-points.applications.drafts.step') }}" data-draft-status-url="{{ route('focal-points.applications.drafts.status') }}" data-draft-lookup-url="{{ route('focal-points.applications.drafts.lookup') }}" data-resume-request-url="{{ route('focal-points.applications.drafts.resume.request') }}" @unless($showApplicationForm) hidden @endunless novalidate>
            @csrf
            <input type="hidden" name="tor_version" value="{{ $torVersion }}">
            <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px" value="">
            <fieldset class="step" data-step="0">
                <legend>Personal information</legend><p class="section-intro">Tell us who you are and where you live.</p>
                <div class="grid">
                    <div class="field wide"><label for="full_name">Full name <span class="required">*</span></label><input id="full_name" name="full_name" value="{{ old('full_name') }}" required autocomplete="name"></div>
                    <div class="field"><label for="primary_phone">Primary phone number <span class="required">*</span></label><input id="primary_phone" name="primary_phone" type="tel" value="{{ old('primary_phone') }}" required autocomplete="tel"></div>
                    <div class="field"><label for="other_phone">Other phone number (if different from the primary phone number)</label><input id="other_phone" name="other_phone" type="tel" value="{{ old('other_phone') }}"></div>
                    <div class="field"><label for="email">Email address <span class="required">*</span></label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></div>
                    <div class="field"><label for="age">Age <span class="required">*</span></label><input id="age" name="age" type="number" min="1" max="120" value="{{ old('age') }}" required></div>
                    @php
                        $choices = [
                            'gender' => ['Gender', ['male' => 'Male', 'female' => 'Female']],
                            'has_disability' => ['Person with a disability?', ['yes' => 'Yes', 'no' => 'No']],
                            'education' => ['Highest level of education', ['bachelors' => "Bachelor's degree", 'postgraduate_diploma' => 'Postgraduate diploma', 'masters' => "Master's degree", 'phd' => 'PhD', 'other' => 'Other']],
                            'resident_in_state' => ['Currently resident in the state you are applying to coordinate?', ['yes' => 'Yes', 'no' => 'No']],
                            'residence_length' => ['Length of residence', ['under_1' => 'Less than 1 year', '1_to_3' => '1–3 years', '4_to_5' => '4–5 years', 'over_5' => 'More than 5 years']],
                            'party_member' => ['Active member of a political party?', ['yes' => 'Yes', 'no' => 'No']],
                            'candidate_2027' => ['Candidate in the 2027 elections?', ['yes' => 'Yes', 'no' => 'No']],
                        ];
                    @endphp
                    @foreach(['gender','has_disability','education'] as $key)
                        <div class="field"><label for="{{ $key }}">{{ $choices[$key][0] }} <span class="required">*</span></label><select id="{{ $key }}" name="{{ $key }}" required><option value="">Select an option</option>@foreach($choices[$key][1] as $value => $text)<option value="{{ $value }}" @selected(old($key) === $value)>{{ $text }}</option>@endforeach</select></div>
                    @endforeach
                    <div class="field wide"><label for="linkedin_url">Link to an active social media profile (LinkedIn preferably)</label><input id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url') }}" placeholder="https://www.linkedin.com/in/..."></div>
                    <div class="field"><label for="state_id">State applying to coordinate <span class="required">*</span></label><div class="location-picker"><select id="state_id" name="state_id" required data-old="{{ old('state_id') }}"><option value="">Loading states…</option></select></div></div>
                    <div class="field"><label for="lga_id">LGA of residence <span class="required">*</span></label><div class="location-picker"><select id="lga_id" name="lga_id" required data-old="{{ old('lga_id') }}" disabled><option value="">Select a state first</option></select></div></div>
                    <div class="field"><label for="ward_id">Electoral ward of residence <span class="required">*</span></label><div class="location-picker"><select id="ward_id" name="ward_id" required data-old="{{ old('ward_id') }}" disabled><option value="">Select an LGA first</option></select></div></div>
                    <div class="field wide"><label for="residential_address">Current residential address <span class="required">*</span></label><textarea id="residential_address" name="residential_address" required>{{ old('residential_address') }}</textarea></div>
                    @foreach(['resident_in_state','residence_length','party_member','candidate_2027'] as $key)
                        <div class="field"><label for="{{ $key }}">{{ $choices[$key][0] }} <span class="required">*</span></label><select id="{{ $key }}" name="{{ $key }}" required><option value="">Select an option</option>@foreach($choices[$key][1] as $value => $text)<option value="{{ $value }}" @selected(old($key) === $value)>{{ $text }}</option>@endforeach</select></div>
                    @endforeach
                </div>
            </fieldset>
            <fieldset class="step" data-step="1" hidden>
                <legend>Election observation experience</legend><p class="section-intro">Describe your previous election observation experience and availability for training.</p>
                <div class="grid">
                    <div class="field wide"><label for="observed_before">Participated in election observation before? <span class="required">*</span></label><select id="observed_before" name="observed_before" aria-controls="observation-experience-field" required><option value="">Select an option</option><option value="yes" @selected(old('observed_before') === 'yes')>Yes</option><option value="no" @selected(old('observed_before') === 'no')>No</option></select></div>
                    <div class="field wide" id="observation-experience-field" hidden><label for="observation_experience">List the elections, organisations, years and your role <span class="required">*</span></label><textarea id="observation_experience" name="observation_experience" disabled>{{ old('observation_experience') }}</textarea></div>
                    <div class="field wide"><label for="coordinated_before">Trained or coordinated a group before? <span class="required">*</span></label><select id="coordinated_before" name="coordinated_before" aria-controls="coordination-experience-field" required><option value="">Select an option</option><option value="yes" @selected(old('coordinated_before') === 'yes')>Yes</option><option value="no" @selected(old('coordinated_before') === 'no')>No</option></select></div>
                    <div class="field wide" id="coordination-experience-field" hidden><label for="coordination_experience">Describe team size and responsibilities <span class="required">*</span></label><textarea id="coordination_experience" name="coordination_experience" disabled>{{ old('coordination_experience') }}</textarea></div>
                    <div class="field"><label for="available_for_training">Available for mandatory training? <span class="required">*</span></label><select id="available_for_training" name="available_for_training" required><option value="">Select an option</option><option value="yes" @selected(old('available_for_training') === 'yes')>Yes</option><option value="no" @selected(old('available_for_training') === 'no')>No</option></select></div>
                </div>
            </fieldset>
            <fieldset class="step" data-step="2" hidden>
                <legend>Coordination and reporting</legend><p class="section-intro">Give practical, concise answers and upload your documents.</p>
                <div class="grid">
                    @foreach([
                        'recruitment_plan' => ['How would you coordinate observers in your state if selected as a coordinator?', 100],
                        'polling_unit_scenario' => ['You arrive at a polling unit at 7 AM. Officials are absent; voters and two security personnel are present. What would you report as an observer?', 50],
                    ] as $key => [$label, $words])
                        <div class="field wide"><label for="{{ $key }}">{{ $label }} <span class="required">*</span></label><textarea id="{{ $key }}" name="{{ $key }}" required data-max-words="{{ $words }}">{{ old($key) }}</textarea><span class="help">{{ $words }} words maximum</span></div>
                    @endforeach
                    <div class="field wide"><span class="label">Tools you can use <span class="required">*</span></span><div class="options">
                        @foreach(['excel' => 'Excel / Google Sheets', 'word' => 'Word / Google Docs', 'google_forms' => 'Google Forms', 'messaging' => 'WhatsApp / messaging', 'online_collection' => 'Online data collection', 'none' => 'None of the above', 'other' => 'Other'] as $value => $label)
                            <label><input type="checkbox" name="tools[]" value="{{ $value }}" @checked(in_array($value, old('tools', [])))> {{ $label }}</label>
                        @endforeach
                    </div></div>
                    <div class="field wide" id="tools-other-field" @if(! in_array('other', old('tools', []))) hidden @endif><label for="tools_other">Other tools <span class="required">*</span></label><input id="tools_other" name="tools_other" value="{{ old('tools_other') }}" @if(in_array('other', old('tools', []))) required @else disabled @endif></div>
                    <div class="field"><label for="cv">CV, maximum two pages <span class="required">*</span></label><input id="cv" name="cv" type="file" accept=".pdf,.doc,.docx" @required(! $draft?->cv_filename)><span class="help">PDF, DOC or DOCX; maximum {{ $cvMaxUploadMb }} MB.</span><span class="saved-file" id="cv-saved" @if(! $draft?->cv_filename) hidden @endif>{{ $draft?->cv_filename ? 'Saved: '.$draft->cv_filename.' Select a new file to replace it.' : '' }}</span><span class="upload-status" id="cv-upload-status" hidden></span></div>
                    <div class="field"><label for="certificate">Highest-qualification certificate <span class="required">*</span></label><input id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @required(! $draft?->certificate_filename)><span class="help">PDF, image, DOC or DOCX; maximum {{ $certificateMaxUploadMb }} MB.</span><span class="saved-file" id="certificate-saved" @if(! $draft?->certificate_filename) hidden @endif>{{ $draft?->certificate_filename ? 'Saved: '.$draft->certificate_filename.' Select a new file to replace it.' : '' }}</span><span class="upload-status" id="certificate-upload-status" hidden></span></div>
                    <div class="field wide"><label style="display:flex;align-items:flex-start;gap:10px;font-weight:500"><input type="checkbox" name="application_consent" value="1" @checked(old('application_consent')) required style="margin-top:5px"> I consent to Dataphyte Foundation processing my answers and uploaded documents to assess this application and contact me about its outcome. <span class="required">*</span></label></div>
                </div>
            </fieldset>
            <fieldset class="step" data-step="3" hidden>
                <legend>Terms of Reference</legend>
                <p class="section-intro">Read the terms to the end before acknowledging them.</p>
                <div class="terms-scroll" id="terms-scroll" tabindex="0" aria-label="Terms of Reference; scroll to read all terms">
                    {!! $torHtml !!}
                </div>
                <p class="help" id="terms-help">Scroll to the end of the terms to enable acknowledgement.</p>
                <label class="terms-accept locked" id="terms-accept-label"><input type="checkbox" id="tor_accepted" name="tor_accepted" value="1" required disabled @checked(old('tor_accepted'))> I have read and agree to the Terms of Reference. <span class="required">*</span></label>
            </fieldset>
            <p class="draft-expiry" id="draft-expiry" hidden></p>
            <p class="save-status" id="save-status" aria-live="polite"></p>
            <div class="actions"><button type="button" class="secondary" id="back-button" hidden>Back</button><button type="button" id="next-button">Continue</button><button type="submit" id="submit-button" disabled hidden>Submit application</button></div>
        </form>
    @endif
</main>
@if($isOpen && !session('application_received'))
@php
    $initialDraft = $draft ? ['answers' => $draft->answers, 'lastCompletedStep' => $draft->last_completed_step, 'cvFilename' => $draft->cv_filename, 'certificateFilename' => $draft->certificate_filename, 'cvUploadStatus' => $draft->cv_upload_status, 'cvUploadError' => $draft->cv_upload_error, 'certificateUploadStatus' => $draft->certificate_upload_status, 'certificateUploadError' => $draft->certificate_upload_error, 'expiresAt' => $draft->expires_at?->toIso8601String()] : null;
@endphp
<script>
(() => {
    const form = document.querySelector('#application-form');
    const hero = document.querySelector('#application-hero');
    const intro = document.querySelector('#application-intro');
    const steps = [...form.querySelectorAll('[data-step]')];
    const nav = document.querySelector('.head-nav');
    const labels = [...document.querySelectorAll('[data-step-label]')];
    const back = document.querySelector('#back-button');
    const next = document.querySelector('#next-button');
    const submit = document.querySelector('#submit-button');
    const terms = document.querySelector('#terms-scroll');
    const termsCheck = document.querySelector('#tor_accepted');
    const termsHelp = document.querySelector('#terms-help');
    const saveStatus = document.querySelector('#save-status');
    const draftExpiry = document.querySelector('#draft-expiry');
    const toolsOtherField = document.querySelector('#tools-other-field');
    const toolsOther = document.querySelector('#tools_other');
    const entry = document.querySelector('#application-entry');
    const pending = document.querySelector('#pending-application');
    const submitted = document.querySelector('#submitted-application');
    const entryEmail = document.querySelector('#entry-email');
    const entryMessage = document.querySelector('#entry-message');
    const pendingMessage = document.querySelector('#pending-message');
    const entryContinue = document.querySelector('#entry-continue');
    const resumeLink = document.querySelector('#send-resume-link');
    const startAgain = document.querySelector('#start-again');
    const initialDraft = @json($initialDraft);
    const storageKey = 'focal-point-application-unsaved-step';
    let serverDraft = initialDraft;
    for (const [answerId, detailId] of [['observed_before', 'observation_experience'], ['coordinated_before', 'coordination_experience']]) {
        const answer = document.querySelector(`#${answerId}`);
        const detail = document.querySelector(`#${detailId}`);
        const field = detail.closest('.field');
        const sync = () => {
            const visible = answer.value === 'yes';
            field.hidden = !visible;
            detail.disabled = !visible;
            detail.required = visible;
            answer.setAttribute('aria-expanded', String(visible));
            if (!visible) detail.value = '';
        };
        answer.addEventListener('change', sync);
        sync();
    }
    const syncToolsOther = () => {
        const selected = form.querySelector('[name="tools[]"][value="other"]').checked;
        toolsOtherField.hidden = !selected;
        toolsOther.disabled = !selected;
        toolsOther.required = selected;
        if (!selected) toolsOther.value = '';
    };
    form.querySelectorAll('[name="tools[]"]').forEach(field => field.addEventListener('change', syncToolsOther));
    syncToolsOther();
    const updateSubmitState = () => {
        submit.disabled = !@json($isReady) || termsCheck.disabled || !termsCheck.checked;
    };
    const unlockTerms = () => {
        if (terms.scrollTop + terms.clientHeight < terms.scrollHeight - 8) return;
        termsCheck.disabled = false;
        document.querySelector('#terms-accept-label').classList.remove('locked');
        termsHelp.textContent = 'You have reached the end. Confirm your agreement below.';
        updateSubmitState();
    };
    terms.addEventListener('scroll', unlockTerms, {passive: true});
    termsCheck.addEventListener('change', updateSubmitState);
    let current = 0;
    const setSaveStatus = (message, state = '') => {
        saveStatus.textContent = message;
        if (state) saveStatus.dataset.state = state;
        else delete saveStatus.dataset.state;
    };
    let uploadPoll = null;
    const savedFile = (kind, filename, status = 'ready', error = '') => {
        const input = form.querySelector(`#${kind}`);
        const label = form.querySelector(`#${kind}-saved`);
        const uploadStatus = form.querySelector(`#${kind}-upload-status`);
        const waiting = ['queued', 'uploading'].includes(status);
        input.required = !filename && !waiting;
        label.hidden = !filename || waiting;
        label.textContent = filename ? `Saved: ${filename}. Select a new file to replace it.` : '';
        uploadStatus.hidden = status === 'ready';
        uploadStatus.dataset.state = status;
        uploadStatus.textContent = status === 'queued' ? 'Document upload is waiting to start.'
            : status === 'uploading' ? 'Document is uploading securely. Please wait.'
                : status === 'failed' ? (error || 'Document upload failed. Select the file again to retry.') : '';
    };
    const formatExpiry = value => new Intl.DateTimeFormat('en-NG', {dateStyle: 'medium', timeStyle: 'short', timeZone: 'Africa/Lagos'}).format(new Date(value));
    const updateDraftMeta = payload => {
        serverDraft = {answers: payload.answers, lastCompletedStep: payload.last_completed_step, cvFilename: payload.cv_filename, certificateFilename: payload.certificate_filename, cvUploadStatus: payload.cv_upload_status, cvUploadError: payload.cv_upload_error, certificateUploadStatus: payload.certificate_upload_status, certificateUploadError: payload.certificate_upload_error, expiresAt: payload.expires_at};
        savedFile('cv', serverDraft.cvFilename, serverDraft.cvUploadStatus, serverDraft.cvUploadError);
        savedFile('certificate', serverDraft.certificateFilename, serverDraft.certificateUploadStatus, serverDraft.certificateUploadError);
        draftExpiry.hidden = !serverDraft.expiresAt;
        draftExpiry.textContent = serverDraft.expiresAt ? `Your saved application expires on ${formatExpiry(serverDraft.expiresAt)} WAT.` : '';
    };
    const uploadsReady = () => serverDraft?.cvUploadStatus === 'ready' && serverDraft?.certificateUploadStatus === 'ready';
    const pollUploadStatus = () => {
        if (uploadPoll || uploadsReady() || !serverDraft) return;
        uploadPoll = window.setInterval(async () => {
            try {
                const response = await fetch(form.dataset.draftStatusUrl, {credentials: 'same-origin', headers: {Accept: 'application/json'}});
                if (!response.ok) return;
                const payload = await response.json();
                updateDraftMeta(payload);
                if (uploadsReady() || ['failed'].includes(serverDraft.cvUploadStatus) || ['failed'].includes(serverDraft.certificateUploadStatus)) {
                    window.clearInterval(uploadPoll); uploadPoll = null;
                    if (uploadsReady()) setSaveStatus('Documents uploaded. You can continue.');
                }
            } catch (_) {}
        }, 2000);
    };
    const applyAnswers = answers => {
        if (!answers) return;
        Object.entries(answers).forEach(([name, value]) => {
            if (name === 'tools') {
                form.querySelectorAll('[name="tools[]"]').forEach(field => { field.checked = Array.isArray(value) && value.includes(field.value); });
                return;
            }
            const field = form.querySelector(`[name="${CSS.escape(name)}"]`);
            if (!field || ['website', 'cv', 'certificate'].includes(name)) return;
            field.value = value ?? '';
            if (field.matches('select')) field.dispatchEvent(new Event('change'));
        });
        ['state_id', 'lga_id', 'ward_id'].forEach(name => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && answers[name]) field.dataset.old = answers[name];
        });
    };
    const readLocal = () => {
        try {
            const saved = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
            if (saved && Date.now() - saved.savedAt < 30 * 60 * 1000) return saved;
        } catch (_) {
        }
        return null;
    };
    const stashCurrentStep = () => {
        const data = {};
        new FormData(form).forEach((value, name) => {
            if (value instanceof File || name === '_token' || name === 'tor_version' || name === 'website') return;
            if (name.endsWith('[]')) {
                const key = name.slice(0, -2);
                data[key] = [...(data[key] || []), value];
            } else data[name] = value;
        });
        try { sessionStorage.setItem(storageKey, JSON.stringify({savedAt: Date.now(), current, answers: data})); } catch (_) {}
    };
    const clearLocal = () => { try { sessionStorage.removeItem(storageKey); } catch (_) {} };
    const scrollLabelIntoView = index => {
        const navBounds = nav.getBoundingClientRect();
        const activeBounds = labels[index].getBoundingClientRect();
        nav.scrollTo({
            left: nav.scrollLeft + activeBounds.left - navBounds.left - (navBounds.width - activeBounds.width) / 2,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    };
    const show = index => {
        current = index;
        steps.forEach((step, i) => { step.hidden = i !== index; step.disabled = i !== index; });
        labels.forEach((label, i) => {
            label.classList.toggle('current', i === index);
            if (i === index) label.setAttribute('aria-current', 'step');
            else label.removeAttribute('aria-current');
        });
        const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
        scrollLabelIntoView(index);
        back.hidden = index === 0; next.hidden = index === steps.length - 1; submit.hidden = index !== steps.length - 1;
        if (index === steps.length - 1) unlockTerms();
        updateSubmitState();
        steps[index].scrollIntoView({behavior, block: 'start'});
    };
    const pickers = new Map();
    const setupPicker = select => {
        const wrapper = select.parentElement;
        const search = document.createElement('input');
        const results = document.createElement('div');
        search.type = 'search'; search.autocomplete = 'off'; search.placeholder = select.options[0].text;
        search.setAttribute('aria-label', `${form.querySelector(`label[for="${select.id}"]`).textContent.trim()} search`);
        search.setAttribute('aria-autocomplete', 'list'); search.setAttribute('aria-expanded', 'false');
        results.className = 'location-results'; results.id = `${select.id}-results`;
        results.setAttribute('role', 'listbox'); results.hidden = true;
        search.setAttribute('aria-controls', results.id);
        wrapper.append(search, results); wrapper.classList.add('enhanced');
        select.setAttribute('aria-hidden', 'true'); select.tabIndex = -1;
        let matches = [], active = -1;
        const close = () => { results.hidden = true; search.setAttribute('aria-expanded', 'false'); search.removeAttribute('aria-activedescendant'); };
        const draw = () => {
            const query = search.value.trim().toLocaleLowerCase();
            matches = [...select.options].slice(1).filter(option => option.text.toLocaleLowerCase().includes(query));
            results.replaceChildren(); active = -1;
            if (!matches.length) {
                const empty = document.createElement('div'); empty.className = 'location-empty'; empty.textContent = 'No matching locations'; results.append(empty);
            }
            matches.forEach((option, index) => {
                const item = document.createElement('button'); item.type = 'button'; item.className = 'location-option';
                item.id = `${select.id}-option-${index}`; item.setAttribute('role', 'option'); item.textContent = option.text;
                item.addEventListener('pointerdown', event => event.preventDefault());
                item.addEventListener('click', () => choose(index)); results.append(item);
            });
            results.hidden = false; search.setAttribute('aria-expanded', 'true');
        };
        const highlight = index => {
            active = index;
            [...results.querySelectorAll('.location-option')].forEach((item, i) => item.classList.toggle('active', i === index));
            const item = results.querySelectorAll('.location-option')[index];
            if (item) { item.scrollIntoView({block: 'nearest'}); search.setAttribute('aria-activedescendant', item.id); }
        };
        const choose = index => {
            if (!matches[index]) return;
            select.value = matches[index].value; search.value = matches[index].text; search.setCustomValidity(''); close();
            select.dispatchEvent(new Event('change', {bubbles: true}));
        };
        search.addEventListener('focus', () => { if (!search.disabled) draw(); });
        search.addEventListener('input', () => { select.value = ''; search.setCustomValidity(''); select.dispatchEvent(new Event('change', {bubbles: true})); draw(); });
        search.addEventListener('keydown', event => {
            if (event.key === 'Escape') { close(); return; }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault(); if (results.hidden) draw();
                if (matches.length) highlight((active + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % matches.length);
            }
            if (event.key === 'Enter' && !results.hidden) { event.preventDefault(); choose(active >= 0 ? active : 0); }
        });
        search.addEventListener('blur', () => setTimeout(close, 100));
        const sync = () => {
            search.disabled = select.disabled;
            search.placeholder = select.options[0]?.text || 'Search locations';
            search.value = select.selectedOptions[0]?.value ? select.selectedOptions[0].text : '';
            close();
        };
        pickers.set(select, {search, sync}); sync();
    };
    const fill = (select, items, placeholder, oldValue = '') => {
        select.replaceChildren(new Option(placeholder, ''), ...items.map(item => new Option(item.name, item.id)));
        select.disabled = false;
        if (oldValue) select.value = oldValue;
        pickers.get(select)?.sync();
    };
    const state = form.querySelector('#state_id'), lga = form.querySelector('#lga_id'), ward = form.querySelector('#ward_id');
    [state, lga, ward].forEach(setupPicker);
    const base = @json(route('focal-points.applications.show'));
    async function load(url, select, placeholder, oldValue = '') {
        select.replaceChildren(new Option('Loading…', '')); select.disabled = true; pickers.get(select).sync();
        try {
            const response = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Location lookup failed');
            const payload = await response.json();
            fill(select, payload.data || [], placeholder, oldValue);
        } catch (_) { select.replaceChildren(new Option('Unable to load. Please try again.', '')); pickers.get(select).sync(); }
    }
    state.addEventListener('change', async () => {
        lga.replaceChildren(new Option('Select a state first', '')); lga.disabled = true; pickers.get(lga).sync();
        ward.replaceChildren(new Option('Select an LGA first', '')); ward.disabled = true; pickers.get(ward).sync();
        if (state.value) await load(`${base}/locations/states/${encodeURIComponent(state.value)}/lgas`, lga, 'Select an LGA');
    });
    lga.addEventListener('change', async () => {
        ward.replaceChildren(new Option('Select an LGA first', '')); ward.disabled = true; pickers.get(ward).sync();
        if (lga.value) await load(`${base}/locations/states/${encodeURIComponent(state.value)}/lgas/${encodeURIComponent(lga.value)}/wards`, ward, 'Select a ward');
    });
    async function restore() {
        await load(`${base}/locations/states`, state, 'Select a state', state.dataset.old);
        if (state.value) {
            await load(`${base}/locations/states/${encodeURIComponent(state.value)}/lgas`, lga, 'Select an LGA', lga.dataset.old);
            if (lga.value) await load(`${base}/locations/states/${encodeURIComponent(state.value)}/lgas/${encodeURIComponent(lga.value)}/wards`, ward, 'Select a ward', ward.dataset.old);
        }
    }
    function validStep() {
        if (steps[current].contains(termsCheck) && (termsCheck.disabled || !termsCheck.checked)) {
            if (termsCheck.disabled) terms.focus();
            else termsCheck.reportValidity();
            return false;
        }
        const fields = [...steps[current].querySelectorAll('input,select,textarea')];
        for (const field of fields) {
            if (pickers.has(field) && !field.checkValidity()) {
                const search = pickers.get(field).search;
                search.setCustomValidity('Select a location from the list.'); search.reportValidity(); return false;
            }
            if (!field.checkValidity()) { field.reportValidity(); return false; }
            if (field.dataset.maxWords && field.value.trim().split(/\s+/).filter(Boolean).length > Number(field.dataset.maxWords)) {
                field.setCustomValidity(`Use ${field.dataset.maxWords} words or fewer.`); field.reportValidity(); return false;
            }
        }
        return true;
    }
    form.querySelectorAll('[data-max-words]').forEach(field => field.addEventListener('input', () => field.setCustomValidity('')));
    const saveCurrentStep = async () => {
        if (!validStep()) return false;
        setSaveStatus('Saving progress…');
        const body = new FormData(form);
        body.append('step', String(current));
        try {
            const response = await fetch(form.dataset.draftStepUrl, {
                method: 'POST', body, credentials: 'same-origin', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value},
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || 'We could not save your progress.');
            updateDraftMeta(payload);
            ['cv', 'certificate'].forEach(kind => {
                if (form.querySelector(`#${kind}`).files.length) form.querySelector(`#${kind}`).value = '';
            });
            clearLocal();
            if (current === 2 && !uploadsReady()) {
                pollUploadStatus();
                setSaveStatus('Your answers are saved. Wait for both document uploads to finish.', 'error');
                return false;
            }
            setSaveStatus('Saved.');
            return true;
        } catch (error) {
            stashCurrentStep();
            setSaveStatus(`${error.message} Your changes remain on this device until you retry.`, 'error');
            return false;
        }
    };
    labels.forEach((label, index) => label.addEventListener('click', async () => {
        if (index <= current) { show(index); return; }
        for (let step = current; step < index; step++) {
            if (!await saveCurrentStep()) { scrollLabelIntoView(current); return; }
            show(step + 1);
        }
    }));
    next.addEventListener('click', async () => { if (await saveCurrentStep()) show(current + 1); });
    back.addEventListener('click', () => show(current - 1));
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!validStep()) return;
        if (current < steps.length - 1) { show(current + 1); return; }
        steps.forEach(step => step.disabled = false);
        submit.disabled = true; submit.textContent = 'Submitting…';
        form.submit();
    });
    form.addEventListener('input', stashCurrentStep);
    form.addEventListener('change', stashCurrentStep);
    const csrfToken = form.querySelector('[name="_token"]').value;
    const startFresh = email => {
        clearLocal();
        serverDraft = null;
        current = 0;
        form.hidden = false;
        hero.hidden = true;
        intro.hidden = true;
        nav.hidden = false;
        entry.hidden = true;
        pending.hidden = true;
        submitted.hidden = true;
        form.querySelector('#email').value = email;
        show(0);
        form.querySelector('#full_name').focus();
    };
    entryContinue.addEventListener('click', async () => {
        if (!entryEmail.checkValidity()) { entryEmail.reportValidity(); return; }
        entryMessage.textContent = 'Checking your application…';
        entryContinue.disabled = true;
        try {
            const response = await fetch(form.dataset.draftLookupUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({email: entryEmail.value})});
            const payload = await response.json();
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || 'We could not check your application.');
            if (payload.has_draft) {
                entry.hidden = true;
                pending.hidden = false;
                submitted.hidden = true;
                pending.dataset.email = entryEmail.value;
                pendingMessage.textContent = '';
                return;
            }
            if (payload.has_submission) {
                entry.hidden = true;
                pending.hidden = true;
                submitted.hidden = false;
                return;
            }
            startFresh(entryEmail.value);
        } catch (error) {
            entryMessage.textContent = error.message;
        } finally {
            entryContinue.disabled = false;
        }
    });
    resumeLink.addEventListener('click', async () => {
        const email = pending.dataset.email;
        pendingMessage.textContent = 'Sending…';
        resumeLink.disabled = true;
        try {
            const response = await fetch(form.dataset.resumeRequestUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken}, body: JSON.stringify({email})});
            const payload = await response.json();
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || 'We could not send the link.');
            pendingMessage.textContent = 'A secure resume link has been sent. Check your email to continue.';
        } catch (error) { pendingMessage.textContent = error.message; }
        finally { resumeLink.disabled = false; }
    });
    startAgain.addEventListener('click', () => startFresh(pending.dataset.email));
    applyAnswers(serverDraft?.answers);
    syncToolsOther();
    const localDraft = readLocal();
    if (localDraft) {
        applyAnswers(localDraft.answers);
        current = localDraft.current || 0;
        setSaveStatus('Changes on this device have not yet been saved to the server.', 'error');
    } else if (serverDraft) {
        current = Math.min(serverDraft.lastCompletedStep + 1, steps.length - 1);
        updateDraftMeta({
            answers: serverDraft.answers,
            last_completed_step: serverDraft.lastCompletedStep,
            cv_filename: serverDraft.cvFilename,
            certificate_filename: serverDraft.certificateFilename,
            cv_upload_status: serverDraft.cvUploadStatus,
            cv_upload_error: serverDraft.cvUploadError,
            certificate_upload_status: serverDraft.certificateUploadStatus,
            certificate_upload_error: serverDraft.certificateUploadError,
            expires_at: serverDraft.expiresAt,
        });
        setSaveStatus('Saved application restored.');
        pollUploadStatus();
    }
    syncToolsOther();
    restore().then(() => {
        if (serverDraft) {
            hero.hidden = true;
            intro.hidden = true;
            show(current);
            return;
        }
        if (localDraft?.answers?.email) {
            form.hidden = false;
            hero.hidden = true;
            intro.hidden = true;
            nav.hidden = false;
            entry.hidden = true;
            show(current);
        }
    });
    @if($errors->any())
        const errors = @json(array_keys($errors->toArray()));
        const first = errors.map(key => form.querySelector(`[name="${key}"],[name="${key}[]"]`)).find(Boolean);
        if (first) show(steps.indexOf(first.closest('[data-step]')));
    @endif
})();
</script>
@endif
</body>
</html>
