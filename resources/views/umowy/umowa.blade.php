{{-- Szablon umowy/aneksu — jeden plik dla podglądu (PDF z drukowania) i .doc.
     Style są wpisane przy elementach, a tabele mają atrybuty cellpadding
     i bgcolor: Word oraz OpenOffice gubią większość reguł z arkusza, więc
     arkusz zostawiamy tylko dla przeglądarki (marginesy strony, pasek druku). --}}
@php
    $granat = '#1f2a44';
    $szary  = '#6b7280';
    $linia  = '#d5d9e2';
    $serif  = '"Times New Roman", Georgia, serif';
    $sans   = 'Arial, Helvetica, sans-serif';
@endphp
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $tytul }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        body { margin: 0; }
        .pasek-druku {
            position: fixed; top: 0; left: 0; right: 0;
            background: #1f2a44; color: #fff; padding: 8px 16px; text-align: center;
            font-family: Arial, Helvetica, sans-serif; font-size: 10pt;
        }
        .pasek-druku button {
            margin-left: 12px; padding: 5px 14px; border: 0; border-radius: 4px;
            background: #fff; color: #1f2a44; font-weight: bold; cursor: pointer;
        }
        @media print { .pasek-druku { display: none; } .arkusz { padding-top: 0 !important; } }
    </style>
</head>
<body style="font-family: {{ $serif }}; font-size: 11.5pt; color: #111;">

@if($pokazPasek)
    <div class="pasek-druku">
        Podgląd dokumentu — zapisz jako PDF przez okno drukowania
        <button type="button" onclick="window.print()">Drukuj / Zapisz PDF</button>
    </div>
@endif

<div class="arkusz" style="max-width: 700px; margin: 0 auto; padding-top: {{ $pokazPasek ? '56px' : '0' }};">

    {{-- Nagłówek: logo po lewej, data po prawej, pod spodem kreska --}}
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="55%" valign="bottom" style="padding-bottom: 6px;">
                @if($logo)
                    <img src="{{ $logo }}" width="150" height="35" alt="{{ $pracodawca }}" style="display:block;">
                @else
                    <span style="font-family: {{ $sans }}; font-size: 15pt; font-weight: bold; color: {{ $granat }};">{{ $pracodawca }}</span>
                @endif
            </td>
            <td width="45%" valign="bottom" align="right" style="padding-bottom: 6px; font-size: 10.5pt; color: #333;">
                {{ $miejsce }}, dnia {{ $dataZawarcia }}
            </td>
        </tr>
        <tr><td colspan="2" bgcolor="{{ $granat }}" style="height:2px; line-height:2px; font-size:1px;">&nbsp;</td></tr>
    </table>

    {{-- Tytuł --}}
    <p style="font-family: {{ $sans }}; font-size: 15pt; font-weight: bold; text-align: center; color: #111; margin: 26px 0 6px;">
        {{ mb_strtoupper($tytul, 'UTF-8') }}
    </p>
    <p style="text-align: center; font-size: 10pt; color: {{ $szary }}; margin: 0 0 24px;">{{ $wstep }}</p>

    {{-- Strony umowy --}}
    <table width="100%" cellpadding="10" cellspacing="0" border="0" style="margin-bottom: 22px;">
        <tr>
            <td width="50%" valign="top" bgcolor="#fafbfc" style="border: 1px solid {{ $linia }};">
                <span style="font-family: {{ $sans }}; font-size: 8pt; color: {{ $szary }};">PRACODAWCA</span><br>
                <span style="font-size: 12pt; font-weight: bold;">{{ $pracodawca }}</span>
            </td>
            <td width="50%" valign="top" bgcolor="#fafbfc" style="border: 1px solid {{ $linia }};">
                <span style="font-family: {{ $sans }}; font-size: 8pt; color: {{ $szary }};">PRACOWNIK</span><br>
                <span style="font-size: 12pt; font-weight: bold;">{{ $pracownik['imie_nazwisko'] }}</span>
                @if($pracownik['pesel'])<br><span style="font-size: 10pt; color: #444;">PESEL {{ $pracownik['pesel'] }}</span>@endif
                @if($pracownik['adres'])<br><span style="font-size: 10pt; color: #444;">{{ $pracownik['adres'] }}</span>@endif
            </td>
        </tr>
    </table>

    {{-- § 1 --}}
    <p style="font-family: {{ $sans }}; font-size: 10.5pt; font-weight: bold; color: {{ $granat }}; margin: 0 0 4px;">§ 1. WARUNKI ZATRUDNIENIA</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="{{ $linia }}" style="height:1px; line-height:1px; font-size:1px;">&nbsp;</td></tr></table>

    <table width="100%" cellpadding="7" cellspacing="0" border="0" style="margin: 0 0 20px;">
        <tr>
            <td width="45%" style="border-bottom: 1px solid #e3e6ec; font-family: {{ $sans }}; font-size: 9.5pt; color: {{ $szary }};">Stanowisko</td>
            <td style="border-bottom: 1px solid #e3e6ec; font-weight: bold;">{{ $stanowisko ?: '—' }}</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #e3e6ec; font-family: {{ $sans }}; font-size: 9.5pt; color: {{ $szary }};">Miejsce wykonywania pracy (budowa)</td>
            <td style="border-bottom: 1px solid #e3e6ec; font-weight: bold;">{{ $budowa ?: '—' }}</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #e3e6ec; font-family: {{ $sans }}; font-size: 9.5pt; color: {{ $szary }};">Okres</td>
            <td style="border-bottom: 1px solid #e3e6ec; font-weight: bold;">od {{ $od }} do {{ $do }}</td>
        </tr>
        @if($wynagrodzenie)
            <tr>
                <td style="border-bottom: 1px solid #e3e6ec; font-family: {{ $sans }}; font-size: 9.5pt; color: {{ $szary }};">Wynagrodzenie</td>
                <td style="border-bottom: 1px solid #e3e6ec; font-weight: bold;">{{ $wynagrodzenie }}</td>
            </tr>
        @endif
    </table>

    {{-- § 2 --}}
    <p style="font-family: {{ $sans }}; font-size: 10.5pt; font-weight: bold; color: {{ $granat }}; margin: 0 0 4px;">§ 2. POZOSTAŁE WARUNKI</p>
    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="{{ $linia }}" style="height:1px; line-height:1px; font-size:1px;">&nbsp;</td></tr></table>
    <p style="margin: 8px 0 20px; text-align: justify; line-height: 1.5;">{{ $pozostaleWarunki }}</p>

    {{-- § 3 --}}
    @if($uwagi)
        <p style="font-family: {{ $sans }}; font-size: 10.5pt; font-weight: bold; color: {{ $granat }}; margin: 0 0 4px;">§ 3. UWAGI</p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="{{ $linia }}" style="height:1px; line-height:1px; font-size:1px;">&nbsp;</td></tr></table>
        <p style="margin: 8px 0 20px; text-align: justify; line-height: 1.5;">{{ $uwagi }}</p>
    @endif

    {{-- Podpisy --}}
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 70px;">
        <tr>
            <td width="42%" style="border-top: 1px solid #333; padding-top: 6px; text-align: center; font-family: {{ $sans }}; font-size: 8.5pt; color: #555;">podpis pracodawcy</td>
            <td width="16%">&nbsp;</td>
            <td width="42%" style="border-top: 1px solid #333; padding-top: 6px; text-align: center; font-family: {{ $sans }}; font-size: 8.5pt; color: #555;">podpis pracownika</td>
        </tr>
    </table>

</div>
</body>
</html>
