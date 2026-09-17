<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Zgłoszenia od kierowników</title>
</head>
<body style="margin:0; padding:24px; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#1f2a44;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="620" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:620px; border:1px solid #d5d9e2;">
                <tr>
                    <td style="padding:24px 28px 8px 28px;">
                        <div style="font-size:18px; font-weight:bold;">Zgłoszenia od kierowników do obsłużenia</div>
                        <div style="padding-top:10px; font-size:14px; line-height:20px;">
                            Kierownicy zgłosili zjazdy, urlopy albo przeniesienia. Zgłoszenie samo niczego nie zmienia —
                            zmianę pobytu albo nieobecność wstawiają kadry na ekranie <strong>Kadry</strong>, a potem zamykają zgłoszenie.
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 0 28px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;">
                            @foreach ($zgloszenia as $z)
                                <tr>
                                    <td style="padding:10px 0; border-top:1px solid #e6e8ee;">
                                        <div style="font-weight:bold;">
                                            {{ $z->contact ? trim($z->contact->last_name.' '.$z->contact->first_name) : 'pracownik' }}
                                            <span style="font-weight:normal; color:#6b7280;">— {{ optional($z->organization)->nazwaBud }}</span>
                                        </div>
                                        <div style="padding-top:2px;">
                                            {{ $z->rodzajLabel() }}
                                            @if ($z->od || $z->do)
                                                : {{ $z->od ? $z->od->format('d.m.Y') : '…' }} – {{ $z->do ? $z->do->format('d.m.Y') : '…' }}
                                            @endif
                                            @if ($z->plik_nazwa)
                                                <span style="color:#6b7280;">(skan: {{ $z->plik_nazwa }})</span>
                                            @endif
                                        </div>
                                        @if ($z->uwaga)
                                            <div style="padding-top:2px; color:#4b5563;">{{ $z->uwaga }}</div>
                                        @endif
                                        <div style="padding-top:2px; font-size:12px; color:#6b7280;">
                                            zgłosił(a) {{ $z->autor ? trim($z->autor->first_name.' '.$z->autor->last_name) : 'kierownik' }},
                                            {{ optional($z->created_at)->format('d.m.Y H:i') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 28px 28px 28px;">
                        <a href="{{ $adresZakladki }}" style="display:inline-block; padding:10px 18px; background-color:#4f46e5; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold;">Otwórz Kadry</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
