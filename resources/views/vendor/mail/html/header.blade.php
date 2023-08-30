<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
{{--<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">--}}
        <img src="https://hrm.mkl.pl/img/MKL-BAU.png" class="logo" alt="MKL BAU">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
