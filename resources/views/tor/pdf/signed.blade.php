@php($organisation = $invitation->flow->organisation)
<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{margin:106px 45px 96px}
body{font-family:DejaVu Sans;font-size:9.5pt;line-height:1.32;color:#111}
article p{margin:0 0 7px}article h2{font-size:14.5pt;line-height:1.16;margin:14px 0 6px;page-break-after:avoid}article h3{font-size:11.5pt;line-height:1.18;margin:12px 0 5px;page-break-after:avoid}article ul,article ol{margin:0 0 8px;padding-left:23px}article li{margin:0 0 4px;padding-left:1px}article li p{margin:0}article li:last-child{margin-bottom:0}
.footer{position:fixed;bottom:-78px;left:0;right:0;font-size:8pt;color:#555;border-top:1px solid #ccc;padding-top:6px}.footer .page:after{content:counter(page)}
.certificate-page-break{page-break-after:always}.certificate{border:1px solid #ddd;padding:28px}.certificate h1{font-size:20pt;letter-spacing:.02em;margin:0 0 20px}.signature{max-height:95px;max-width:260px}
</style></head><body>
<div class="footer">{{ $invitation->flow->title }} <span style="float:right">Page <span class="page"></span></span></div>
<article>{!! $invitation->flow->content_html !!}</article>
<div class="certificate-page-break"></div>
<section class="certificate"><h1>Term Of Reference Signing Record</h1><p><strong>{{ $organisation->name }}</strong></p><p>I, <strong>{{ $invitation->signer_name }}</strong>, confirm that I have read and agreed to all terms listed in <strong>{{ $invitation->flow->title }}</strong>.</p><p><strong>Signer name:</strong> {{ $invitation->signer_name }}<br><strong>Email:</strong> {{ $invitation->email }}<br><strong>Signed:</strong> {{ $invitation->signed_at?->format('d M Y, H:i T') }}<br><strong>Verification code:</strong> {{ $invitation->verification_code }}</p>
@foreach($invitation->flow->signerSections() as $section)
<h3>{{ $section['heading'] }}</h3>
@foreach($section['fields'] as $field)
@php($value = ($invitation->signer_field_values ?? [])[$field['handle']] ?? null)
<p><strong>{{ $field['label'] }}:</strong> @if(is_array($value)){{ collect(['Bank' => $value['bank_name'] ?? null, 'Account number' => $value['account_number'] ?? null, 'Account name' => $value['account_name'] ?? null])->filter()->map(fn ($item, $label) => $label.': '.$item)->implode(' · ') ?: '—' }}@elseif($field['type'] === 'checkbox'){{ $value ? 'Yes' : 'No' }}@else{{ $value ?? '—' }}@endif</p>
@endforeach
@endforeach
<p><strong>Signature</strong></p>@if($invitation->signature_method === 'snap')<img class="signature" src="{{ storage_path('app/private/'.$invitation->signature_data) }}">@elseif($invitation->signature_method === 'draw')<img class="signature" src="{{ $invitation->signature_data }}">@else<p style="font-family:cursive;font-size:24pt">{{ $invitation->signer_name }}</p>@endif</section>
</body></html>
