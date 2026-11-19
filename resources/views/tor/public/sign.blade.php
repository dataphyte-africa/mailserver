<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $invitation->flow->title }}</title>
<style>
:root{color-scheme:light}*{box-sizing:border-box}body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f3f6fa;margin:0;color:#17233a}.wrap{background:#fff;border:1px solid #dbe4f0;border-radius:18px;box-shadow:0 18px 48px rgba(23,35,58,.08);margin:24px auto;max-width:720px;padding:30px;width:calc(100% - 32px)}.logo{max-height:52px;max-width:190px}.wrap>h1{font-size:clamp(27px,5vw,36px);letter-spacing:-.035em;line-height:1.12;margin:26px 0 30px}.tor{font-size:16px;line-height:1.72}.tor h2{font-size:22px;letter-spacing:-.02em;line-height:1.25;margin:34px 0 13px}.tor li{margin:9px 0}.sign{margin-top:36px;padding-top:28px;border-top:1px solid #dbe4f0}input,select,textarea{width:100%;padding:12px 13px;margin:7px 0 16px;border:1px solid #bdcadb;border-radius:8px;background:#fff;color:#17233a;font:inherit}input:focus,select:focus,textarea:focus{border-color:#1769d1;box-shadow:0 0 0 3px rgba(23,105,209,.13);outline:0}button{background:#123c52;color:#fff;border:0;padding:12px 18px;border-radius:8px;font:600 15px/1.2 inherit;cursor:pointer}.signature{background:#fff;border:1px solid #aab9cc;border-radius:10px;cursor:crosshair;height:170px;touch-action:none;width:100%}.signature-actions{display:flex;justify-content:flex-end;margin:8px 0 16px}.signature-clear{background:#fff;border:1px solid #9aabc0;color:#244364;font-size:14px;padding:7px 12px}.signer-section{border-top:1px solid #e1e8f1;margin-top:28px;padding-top:25px}.signer-section h2{font-size:20px;margin:0 0 5px}.field-help{color:#596b83;font-size:14px;margin:-10px 0 14px}.field-status{color:#53677f;font-size:14px;margin:8px 0 0}.field-status.error,.field-error{color:#a12727}.field-error{display:block;font-size:14px;margin:-9px 0 12px}.errors{background:#fff2f2;border:1px solid #edb8b8;border-radius:9px;color:#942727;margin:18px 0;padding:12px 15px}.errors ul{margin:6px 0 0;padding-left:20px}.bank-account-card{background:linear-gradient(135deg,#f8fbff,#f3f8ff);border:1px solid #cbdcf2;border-radius:14px;margin:10px 0 18px;padding:20px}.bank-account-card label{display:block;font-weight:650}.bank-account-card input{margin-bottom:0}.bank-label{display:block;font-size:14px;font-weight:700;margin-bottom:8px}.bank-picker{position:relative}.bank-picker-input{padding-right:50px}.bank-picker-toggle{align-items:center;background:transparent;color:#31516f;display:flex;height:44px;justify-content:center;padding:0;position:absolute;right:3px;top:3px;width:42px}.bank-picker-toggle:hover{background:#e7f0fb}.bank-picker-toggle svg{height:18px;width:18px}.bank-options{background:#fff;border:1px solid #b9cce4;border-radius:10px;box-shadow:0 16px 32px rgba(25,51,83,.16);left:0;margin-top:5px;max-height:250px;overflow:auto;position:absolute;right:0;z-index:10}.bank-option{background:#fff;color:#17233a;display:block;text-align:left;width:100%;border-radius:0;padding:11px 13px}.bank-option:hover,.bank-option[aria-selected="true"]{background:#eaf3ff;color:#0d58aa}.bank-empty{color:#61738a;font-size:14px;padding:12px}.bank-account-grid{display:grid;gap:15px;grid-template-columns:1fr 1fr;margin-top:16px}.account-name{background:#eef5ff;border-color:#b8cde7;color:#23466a}.account-confirmation,.agreement{align-items:flex-start;display:flex!important;font-size:14px;gap:9px;line-height:1.45;margin-top:17px}.account-confirmation input,.agreement input{height:18px;margin:1px 0 0;width:18px}.signing-confirmation{background:#f7faff;border:1px solid #cfdef0;border-radius:14px;margin-top:30px;padding:22px}.signing-confirmation h2{font-size:20px;margin:0 0 5px}.signing-intro{color:#52677f;margin:0 0 19px}.signing-field{display:block;font-size:14px;font-weight:700}.signing-field input,.signing-field select{margin-top:8px}.signature-upload input[type=file]{background:#fff;border-style:dashed;padding:8px}.signature-upload input[type=file]::file-selector-button{background:#e9f2ff;border:0;border-radius:6px;color:#164f8a;cursor:pointer;font:600 14px inherit;margin-right:10px;padding:8px 10px}.signing-audit{color:#50647c;font-size:14px;margin:20px 0}.complete-signing{background:#123c52;box-shadow:0 4px 10px rgba(18,60,82,.2);width:100%}@media(max-width:640px){.wrap{border:0;border-radius:0;margin:0;padding:24px 18px;width:100%}.bank-account-grid{grid-template-columns:1fr}.bank-account-card,.signing-confirmation{padding:17px}.tor{font-size:15px}}
</style>
</head><body><main class="wrap">
@if($invitation->flow->organisation->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($invitation->flow->organisation->logo_path) }}" alt="{{ $invitation->flow->organisation->name }}">@endif
<h1>{{ $invitation->flow->title }}</h1>
@if($invitation->isComplete())
<p>Your TOR was signed on {{ $invitation->signed_at->format('d M Y, H:i') }}.</p><a href="{{ route('tor.public.download', $invitation->token, false) }}">Download your signed TOR PDF</a>
@else
@if(session('success'))<p>{{ session('success') }}</p>@endif
@if($errors->any())<div class="errors" role="alert"><strong>Your signing could not be completed.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="tor">{!! $invitation->flow->content_html !!}</div>
@if($signingUnavailableMessage)
<div class="errors" role="status"><strong>{{ $signingUnavailableMessage }}</strong></div>
@else
<form class="sign" method="POST" enctype="multipart/form-data" action="{{ route('tor.public.sign', $invitation->token, false) }}">@csrf
@foreach($invitation->flow->signerSections() as $section)
<section class="signer-section"><h2>{{ $section['heading'] }}</h2>
@if(filled($section['instructions'] ?? null))<p class="field-help">{{ $section['instructions'] }}</p>@endif
@foreach($section['fields'] as $field)
<div class="signer-field">
@if($field['type'] === 'checkbox')
<input type="checkbox" name="signer_fields[{{ $field['handle'] }}]" value="1" @checked(old('signer_fields.'.$field['handle'])) style="width:auto"> {{ $field['label'] }} @if($field['required'])<span aria-label="required">*</span>@endif
@elseif($field['type'] === 'bank_account')
<div class="bank-account-card" data-bank-picker>
<span class="bank-label">{{ $field['label'] }} @if($field['required'])<span aria-label="required">*</span>@endif</span>
<div class="bank-picker">
<input class="bank-picker-input" data-bank-search value="{{ old('signer_fields.'.$field['handle'].'.bank_name') }}" placeholder="Search for your bank" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="bank-options-{{ $field['handle'] }}" @required($field['required'])>
<button class="bank-picker-toggle" data-bank-toggle type="button" aria-label="Show bank choices" aria-expanded="false"><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7 5 5 5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg></button>
<div class="bank-options" data-bank-list id="bank-options-{{ $field['handle'] }}" role="listbox" hidden></div>
</div>
<input type="hidden" data-bank-code name="signer_fields[{{ $field['handle'] }}][bank_code]" value="{{ old('signer_fields.'.$field['handle'].'.bank_code') }}">
@error('signer_fields.'.$field['handle'].'.bank_code')<span class="field-error">{{ $message }}</span>@enderror
<div class="bank-account-grid"><label>Account number<input class="account-number" data-account-number name="signer_fields[{{ $field['handle'] }}][account_number]" value="{{ old('signer_fields.'.$field['handle'].'.account_number') }}" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="10-digit account number" @required($field['required'])></label><label>Verified account name<input class="account-name" data-account-name value="{{ old('signer_fields.'.$field['handle'].'.account_name') }}" readonly placeholder="Will appear after verification" aria-describedby="bank-status-{{ $field['handle'] }}"></label></div>
@error('signer_fields.'.$field['handle'].'.account_number')<span class="field-error">{{ $message }}</span>@enderror
<label class="account-confirmation"><input type="checkbox" data-account-confirmed name="signer_fields[{{ $field['handle'] }}][account_name_confirmed]" value="1" disabled @checked(old('signer_fields.'.$field['handle'].'.account_name_confirmed'))> I confirm the complete account name shown above belongs to this bank account.</label>
@error('signer_fields.'.$field['handle'].'.account_name_confirmed')<span class="field-error">{{ $message }}</span>@enderror
<p class="field-status" data-bank-status id="bank-status-{{ $field['handle'] }}" aria-live="polite"></p>
</div>
@else
{{ $field['label'] }} @if($field['required'])<span aria-label="required">*</span>@endif
@if($field['type'] === 'textarea')<textarea name="signer_fields[{{ $field['handle'] }}]" @required($field['required'])>{{ old('signer_fields.'.$field['handle']) }}</textarea>
@else<input type="{{ $field['type'] === 'date' ? 'date' : 'text' }}" name="signer_fields[{{ $field['handle'] }}]" value="{{ old('signer_fields.'.$field['handle']) }}" @required($field['required'])>@endif
@endif
</div>
@if(filled($field['help'] ?? null))<p class="field-help">{{ $field['help'] }}</p>@endif
@endforeach</section>
@endforeach
<section class="signing-confirmation"><h2>Review and sign</h2><p class="signing-intro">Confirm your agreement, then choose how you would like to sign this record.</p>
<label class="agreement"><input type="checkbox" name="agree" value="1" required> I have read and agree to the terms above.</label>
<label class="signing-field">Full name<input name="full_name" required value="{{ old('full_name', $invitation->expected_name) }}"></label>
<label class="signing-field">Signature method<select id="method" name="signature_method"><option value="snap" @selected(old('signature_method', 'snap') === 'snap')>Snap your signature</option><option value="draw" @selected(old('signature_method') === 'draw')>Draw your signature</option><option value="type" @selected(old('signature_method') === 'type')>Type your full name</option></select></label>
<div id="snap" class="signature-upload"><input type="file" accept="image/*" capture="environment" name="signature_upload"></div><div id="draw" hidden><canvas id="canvas" class="signature" aria-label="Draw your signature"></canvas><div class="signature-actions"><button class="signature-clear" id="clear-signature" type="button">Clear signature</button></div><input id="signature_drawn" type="hidden" name="signature_drawn"></div><p id="typed" hidden>Your entered full name will be used as your signature.</p><p class="signing-audit">Your IP address and signing time are recorded to protect this signing record.</p><button class="complete-signing" type="submit">Complete signing</button></section>
</form>
<script>
const method=document.getElementById('method'),snap=document.getElementById('snap'),draw=document.getElementById('draw'),typed=document.getElementById('typed'),canvas=document.getElementById('canvas'),context=canvas.getContext('2d'),signatureInput=document.getElementById('signature_drawn'),clearButton=document.getElementById('clear-signature');let drawing=false,canvasReady=false;
function resizeCanvas(){const rect=canvas.getBoundingClientRect(),scale=window.devicePixelRatio||1;canvas.width=Math.max(1,Math.floor(rect.width*scale));canvas.height=Math.max(1,Math.floor(rect.height*scale));context.setTransform(scale,0,0,scale,0,0);context.lineWidth=2;context.lineCap='round';context.lineJoin='round';context.strokeStyle='#17212b';canvasReady=true}
function setMode(){snap.hidden=method.value!=='snap';draw.hidden=method.value!=='draw';typed.hidden=method.value!=='type';if(method.value==='draw'&&!canvasReady){requestAnimationFrame(resizeCanvas)}}
function point(event){const source=event.touches?event.touches[0]:event,rect=canvas.getBoundingClientRect();return{x:source.clientX-rect.left,y:source.clientY-rect.top}}
function start(event){event.preventDefault();if(!canvasReady)resizeCanvas();const p=point(event);drawing=true;context.beginPath();context.moveTo(p.x,p.y);context.lineTo(p.x+.01,p.y+.01);context.stroke();if(event.pointerId!==undefined)canvas.setPointerCapture?.(event.pointerId)}
function move(event){if(!drawing)return;event.preventDefault();const p=point(event);context.lineTo(p.x,p.y);context.stroke()}
function finish(event){if(!drawing)return;drawing=false;if(event?.pointerId!==undefined&&canvas.hasPointerCapture?.(event.pointerId))canvas.releasePointerCapture(event.pointerId);signatureInput.value=canvas.toDataURL('image/png')}
function clearSignature(){if(!canvasReady)resizeCanvas();context.clearRect(0,0,canvas.width,canvas.height);signatureInput.value=''}
method.addEventListener('change',setMode);clearButton.addEventListener('click',clearSignature);if(window.PointerEvent){canvas.addEventListener('pointerdown',start);canvas.addEventListener('pointermove',move);canvas.addEventListener('pointerup',finish);canvas.addEventListener('pointercancel',finish)}else{canvas.addEventListener('mousedown',start);canvas.addEventListener('mousemove',move);window.addEventListener('mouseup',finish);canvas.addEventListener('touchstart',start,{passive:false});canvas.addEventListener('touchmove',move,{passive:false});canvas.addEventListener('touchend',finish)}setMode();
const bankUrl=@json(route('tor.public.banks',$invitation->token,false)),resolveBankUrl=@json(route('tor.public.resolve-bank-account',$invitation->token,false)),csrf=document.querySelector('input[name="_token"]')?.value,oldSignerFields=@json(old('signer_fields', []));
document.querySelectorAll('[data-bank-picker]').forEach(async wrapper=>{
    const codeInput=wrapper.querySelector('[data-bank-code]'),bankSearch=wrapper.querySelector('[data-bank-search]'),bankList=wrapper.querySelector('[data-bank-list]'),toggle=wrapper.querySelector('[data-bank-toggle]'),accountNumber=wrapper.querySelector('[data-account-number]'),accountName=wrapper.querySelector('[data-account-name]'),confirmed=wrapper.querySelector('[data-account-confirmed]'),status=wrapper.querySelector('[data-bank-status]'),handle=codeInput.name.match(/\[([^\]]+)\]/)?.[1],oldCode=oldSignerFields[handle]?.bank_code||'';
    let banks=[],timer;
    const clearConfirmation=()=>{accountName.value='';confirmed.checked=false;confirmed.disabled=true};
    const setOpen=open=>{bankList.hidden=!open;bankSearch.setAttribute('aria-expanded',String(open));toggle.setAttribute('aria-expanded',String(open))};
    const renderBanks=query=>{
        const needle=query.trim().toLocaleLowerCase();
        const matches=banks.filter(bank=>bank.name.toLocaleLowerCase().includes(needle)).slice(0,50);
        bankList.replaceChildren();
        if(matches.length===0){const empty=document.createElement('p');empty.className='bank-empty';empty.textContent='No matching bank found.';bankList.append(empty);return}
        matches.forEach(bank=>{const option=document.createElement('button');option.type='button';option.className='bank-option';option.setAttribute('role','option');option.setAttribute('aria-selected',String(bank.code===codeInput.value));option.textContent=bank.name;option.addEventListener('click',()=>selectBank(bank));bankList.append(option)});
    };
    const resolve=async()=>{
        const code=codeInput.value,number=accountNumber.value.trim();
        clearConfirmation();status.classList.remove('error');
        if(!code){status.textContent=bankSearch.value.trim() ? 'Select a bank from the results.' : '';return}
        if(number && !/^\d{10}$/.test(number)){status.textContent='Enter a valid 10-digit account number.';status.classList.add('error');return}
        if(!number)return;
        status.textContent='Verifying account…';
        try{const response=await fetch(resolveBankUrl,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({bank_code:code,account_number:number})}),payload=await response.json();if(!response.ok)throw new Error(payload.message);accountName.value=payload.data.account_name;confirmed.disabled=false;status.textContent='Review the complete account name, then confirm it is correct.'}
        catch{accountName.value='';status.textContent='We could not verify a complete account name. Check the bank and account number, then try again.';status.classList.add('error')}
    };
    const selectBank=bank=>{bankSearch.value=bank.name;codeInput.value=bank.code;setOpen(false);clearTimeout(timer);resolve()};
    try{const response=await fetch(bankUrl,{headers:{Accept:'application/json'}}),payload=await response.json();if(!response.ok)throw new Error();banks=payload.data;if(oldCode){const bank=banks.find(bank=>bank.code===oldCode);if(bank){bankSearch.value=bank.name;codeInput.value=bank.code;resolve()}}}
    catch{status.textContent='Bank choices are temporarily unavailable. Please refresh and try again.';status.classList.add('error')}
    bankSearch.addEventListener('focus',()=>{renderBanks(bankSearch.value);setOpen(true)});
    bankSearch.addEventListener('input',()=>{codeInput.value='';clearConfirmation();renderBanks(bankSearch.value);setOpen(true);clearTimeout(timer);timer=setTimeout(resolve,150)});
    bankSearch.addEventListener('keydown',event=>{if(event.key==='Escape')setOpen(false)});
    toggle.addEventListener('click',()=>{const open=bankList.hidden;renderBanks(bankSearch.value);setOpen(open);if(open)bankSearch.focus()});
    accountNumber.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(resolve,500)});
    document.addEventListener('click',event=>{if(!wrapper.contains(event.target))setOpen(false)});
});
</script>
@endif
@endif
</main></body></html>
