<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center" style="padding: 32px 40px;">
{{-- Divider --}}
<div style="height: 1px; background-color: #e4e4e7; margin-bottom: 20px;"></div>
{{ Illuminate\Mail\Markdown::parse($slot) }}
<p style="color: #a1a1aa; font-size: 11px; margin-top: 12px; margin-bottom: 0;">
{{ __('This is an automated message from') }} <a href="{{ config('app.url') }}" style="color: #71717a;">{{ config('app.name') }}</a>.<br>
{{ __('Please do not reply directly to this email.') }}
</p>
</td>
</tr>
</table>
</td>
</tr>
