<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Zmiany kadrowe</title>
</head>
<body style="margin:0; padding:24px; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#1f2a44;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="620" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:620px; border:1px solid #d5d9e2;">
                <tr>
                    <td style="padding:24px 28px 8px 28px;">
                        <div style="font-size:18px; font-weight:bold; color:#1f2a44;">Zmiany kadrowe do obsłużenia</div>
                        <div style="padding-top:10px; font-size:14px; line-height:20px;">
                            @php $ile = $zmiany->pluck('contact_id')->unique()->count(); @endphp
                            W zakładce <strong>Zmiany kadrowe</strong>
                            @if ($ile === 1)
                                czeka zmiana pobytu jednej osoby
                            @else
                                czekają zmiany pobytu {{ $ile }} osób
                            @endif
                            — do przygotowania aneksy do umów.
                        </div>
                        <div style="padding-top:6px; font-size:13px; color:#6b7280;">
                            Wprowadził(a): {{ $autor }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px 0 28px;">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;">
                            @foreach ($zmiany as $zmiana)
                                <tr>
                                    <td style="padding:10px 0; border-top:1px solid #e6e8ee;">
                                        <div style="font-weight:bold;">
                                            @if ($zmiana->contact)
                                                {{ trim($zmiana->contact->last_name.' '.$zmiana->contact->first_name) }}
                                            @else
                                                pracownik
                                            @endif
                                            <span style="font-weight:normal; color:#6b7280;">— {{ $zmiana->typLabel() }}</span>
                                        </div>

                                        @if ($zmiana->budowaZ)
                                            <div style="padding-top:3px; color:#6b7280;">
                                                z: {{ $zmiana->budowaZ->nazwaBud }}
                                                @if ($zmiana->old_start || $zmiana->old_end)
                                                    ({{ optional($zmiana->old_start)->format('d.m.Y') }} – {{ optional($zmiana->old_end)->format('d.m.Y') }})
                                                @endif
                                            </div>
                                        @endif

                                        @if ($zmiana->budowaDo)
                                            <div style="padding-top:3px; color:#6b7280;">
                                                na: {{ $zmiana->budowaDo->nazwaBud }}
                                                @if ($zmiana->new_start || $zmiana->new_end)
                                                    ({{ optional($zmiana->new_start)->format('d.m.Y') }} – {{ optional($zmiana->new_end)->format('d.m.Y') }})
                                                @endif
                                            </div>
                                        @elseif ($zmiana->new_start || $zmiana->new_end)
                                            <div style="padding-top:3px; color:#6b7280;">
                                                nowy termin: {{ optional($zmiana->new_start)->format('d.m.Y') }} – {{ optional($zmiana->new_end)->format('d.m.Y') }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 28px 28px 28px;">
                        <a href="{{ $adresZakladki }}"
                           style="display:inline-block; padding:11px 20px; background-color:#1f2a44; color:#ffffff; font-size:13px; font-weight:bold; text-decoration:none;">
                            Otwórz Zmiany kadrowe
                        </a>
                        <div style="padding-top:14px; font-size:12px; color:#9aa1ad;">
                            Wiadomość wysłana automatycznie z systemu HRM. Nie odpowiadaj na nią.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
