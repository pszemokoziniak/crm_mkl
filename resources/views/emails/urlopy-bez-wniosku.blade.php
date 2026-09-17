<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Urlopy bez wniosku</title>
</head>
<body style="margin:0; padding:24px; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#1f2a44;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="620" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:620px; border:1px solid #d5d9e2;">
                <tr>
                    <td style="padding:24px 28px 8px 28px;">
                        <div style="font-size:18px; font-weight:bold;">Urlopy w KCP bez wniosku urlopowego</div>
                        <div style="padding-top:10px; font-size:14px; line-height:20px;">
                            W KCP Twoich budów są dni urlopu, do których nie ma skanu wniosku. Otwórz KCP budowy,
                            kliknij <strong>„Dodaj wniosek”</strong> przy nazwisku i dołącz zdjęcie albo PDF wniosku —
                            kadry wstawią nieobecność i brak zniknie.
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 0 28px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;">
                            @foreach ($urlopy as $u)
                                <tr>
                                    <td style="padding:8px 0; border-top:1px solid #e6e8ee;">
                                        <strong>{{ $u['pracownik'] }}</strong>
                                        <span style="color:#6b7280;">— {{ $u['budowa'] }}</span><br>
                                        {{ $u['kod'] }} {{ \Carbon\Carbon::parse($u['od'])->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($u['do'])->format('d.m.Y') }}
                                        ({{ $u['dni'] }} {{ $u['dni'] === 1 ? 'dzień' : 'dni' }})
                                        &nbsp;<a href="{{ $adresAplikacji }}/building/{{ $u['organization_id'] }}/time-sheet?date={{ $u['od'] }}" style="color:#4f46e5;">otwórz KCP</a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
