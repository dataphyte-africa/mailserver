<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $invitation->flow->title }}</title>
<style>body{font-family:system-ui;background:#f5f7f8;margin:0;color:#17212b}.wrap{max-width:820px;margin:32px auto;background:#fff;padding:32px;border-radius:10px}.logo{max-height:58px;max-width:220px}.tor{line-height:1.65}.sign{margin-top:32px;padding-top:24px;border-top:1px solid #ddd}input,select,textarea{width:100%;padding:10px;margin:6px 0 16px;box-sizing:border-box}button{background:#123c52;color:#fff;border:0;padding:12px 18px;border-radius:5px}.signature{border:1px solid #aaa;height:170px;touch-action:none;width:100%}.signer-section{border-top:1px solid #e1e5ea;margin-top:24px;padding-top:22px}.signer-section h2{font-size:18px;margin:0 0 4px}.field-help{color:#59677a;font-size:14px;margin:-10px 0 14px}.errors{background:#fff2f2;border:1px solid #edb8b8;border-radius:6px;color:#942727;margin:18px 0;padding:12px 15px}.errors ul{margin:6px 0 0;padding-left:20px}</style>
</head><body><main class="wrap">
@if($invitation->flow->organisation->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($invitation->flow->organisation->logo_path) }}" alt="{{ $invitation->flow->organisation->name }}">@endif
<h1>{{ $invitation->flow->title }}</h1>
@if($invitation->isComplete())
<p>Your TOR was signed on {{ $invitation->signed_at->format('d M Y, H:i') }}.</p><a href="{{ route('tor.public.download', $invitation->token) }}">Download your signed TOR PDF</a>
@else
@if(session('success'))<p>{{ session('success') }}</p>@endif
@if($errors->any())<div class="errors" role="alert"><strong>Your signing could not be completed.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="tor">{!! $invitation->flow->content_html !!}</div>
<form class="sign" method="POST" enctype="multipart/form-data" action="{{ route('tor.public.sign', $invitation->token) }}">@csrf
@foreach($invitation->flow->signerSections() as $section)
<section class="signer-section"><h2>{{ $section['heading'] }}</h2>
@if(filled($section['instructions'] ?? null))<p class="field-help">{{ $section['instructions'] }}</p>@endif
@foreach($section['fields'] as $field)
<label>
@if($field['type'] === 'checkbox')
<input type="checkbox" name="signer_fields[{{ $field['handle'] }}]" value="1" @checked(old('signer_fields.'.$field['handle'])) style="width:auto"> {{ $field['label'] }} @if($field['required'])<span aria-label="required">*</span>@endif
@else
{{ $field['label'] }} @if($field['required'])<span aria-label="required">*</span>@endif
@if($field['type'] === 'textarea')<textarea name="signer_fields[{{ $field['handle'] }}]" @required($field['required'])>{{ old('signer_fields.'.$field['handle']) }}</textarea>
@else<input type="{{ $field['type'] === 'date' ? 'date' : 'text' }}" name="signer_fields[{{ $field['handle'] }}]" value="{{ old('signer_fields.'.$field['handle']) }}" @required($field['required'])>@endif
@endif
</label>
@if(filled($field['help'] ?? null))<p class="field-help">{{ $field['help'] }}</p>@endif
@endforeach</section>
@endforeach
<label><input type="checkbox" name="agree" value="1" required style="width:auto"> I have read and agree to the terms above.</label>
<label>Full name<input name="full_name" required value="{{ old('full_name', $invitation->expected_name) }}"></label>
<label>Signature method<select id="method" name="signature_method"><option value="snap" @selected(old('signature_method', 'snap') === 'snap')>Snap your signature</option><option value="draw" @selected(old('signature_method') === 'draw')>Draw your signature</option><option value="type" @selected(old('signature_method') === 'type')>Type your full name</option></select></label>
<div id="snap"><input type="file" accept="image/*" capture="environment" name="signature_upload"></div><div id="draw" hidden><canvas id="canvas" class="signature"></canvas><input id="signature_drawn" type="hidden" name="signature_drawn"></div><p id="typed" hidden>Your entered full name will be used as your signature.</p><p>We record your IP address and signing time for the audit record.</p><button type="submit">Complete signing</button>
</form>
<script>const m=document.getElementById('method'),snap=document.getElementById('snap'),draw=document.getElementById('draw'),typed=document.getElementById('typed'),c=document.getElementById('canvas'),x=c.getContext('2d');let on=false;function mode(){snap.hidden=m.value!=='snap';draw.hidden=m.value!=='draw';typed.hidden=m.value!=='type'}m.onchange=mode;mode();function size(){let r=c.getBoundingClientRect();c.width=r.width;c.height=r.height;x.lineWidth=2;x.lineCap='round'}size();c.onpointerdown=e=>{on=true;c.setPointerCapture(e.pointerId);x.beginPath();x.moveTo(e.offsetX,e.offsetY)};c.onpointermove=e=>{if(on){x.lineTo(e.offsetX,e.offsetY);x.stroke()}};c.onpointerup=()=>{on=false;document.getElementById('signature_drawn').value=c.toDataURL('image/png')};</script>
@endif
</main></body></html>
