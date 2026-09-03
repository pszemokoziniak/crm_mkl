{{-- Szablon umowy/aneksu. Ten sam plik obsługuje podgląd do druku i plik .doc,
     żeby dokument nie rozjechał się między formatami. --}}
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>{{ $tytul }}</title>
    <style>
        @page { size: A4; margin: 20mm 18mm; }
        body { font-family: "Times New Roman", Georgia, serif; font-size: 12pt; line-height: 1.5; color: #000; }
        h1 { font-size: 14pt; text-align: center; margin: 0 0 4mm; text-transform: uppercase; }
        .naglowek { text-align: right; font-size: 11pt; margin-bottom: 8mm; }
        .strony { margin-bottom: 6mm; }
        .strony p { margin: 0 0 2mm; }
        .paragraf { margin-bottom: 4mm; }
        .paragraf h2 { font-size: 12pt; margin: 0 0 1mm; }
        table.dane { width: 100%; border-collapse: collapse; margin: 3mm 0; }
        table.dane td { padding: 1.5mm 2mm; border: 1px solid #999; vertical-align: top; }
        table.dane td.etykieta { width: 42%; background: #f2f2f2; }
        .podpisy { margin-top: 16mm; width: 100%; }
        .podpisy td { width: 50%; text-align: center; font-size: 11pt; padding-top: 14mm; }
        .podpisy .linia { border-top: 1px solid #000; padding-top: 1.5mm; }
        .stopka { margin-top: 8mm; font-size: 9pt; color: #555; }
        @media print { .bez-druku { display: none; } }
    </style>
</head>
<body>
<div class="naglowek">{{ $miejsce }}, dnia {{ $dataZawarcia }}</div>

<h1>{{ $tytul }}</h1>

<div class="strony">
    <p><strong>Pracodawca:</strong> {{ $pracodawca }}</p>
    <p><strong>Pracownik:</strong> {{ $pracownik['imie_nazwisko'] }}@if($pracownik['pesel']), PESEL {{ $pracownik['pesel'] }}@endif</p>
    @if($pracownik['adres'])
        <p><strong>Adres:</strong> {{ $pracownik['adres'] }}</p>
    @endif
</div>

<div class="paragraf">
    <h2>§ 1. Przedmiot</h2>
    <p>{{ $wstep }}</p>
    <table class="dane">
        <tr><td class="etykieta">Stanowisko</td><td>{{ $stanowisko ?: '—' }}</td></tr>
        <tr><td class="etykieta">Miejsce wykonywania pracy (budowa)</td><td>{{ $budowa ?: '—' }}</td></tr>
        <tr><td class="etykieta">Okres</td><td>od {{ $od }} do {{ $do }}</td></tr>
        @if($wynagrodzenie)
            <tr><td class="etykieta">Wynagrodzenie</td><td>{{ $wynagrodzenie }}</td></tr>
        @endif
    </table>
</div>

<div class="paragraf">
    <h2>§ 2. Pozostałe warunki</h2>
    <p>{{ $pozostaleWarunki }}</p>
</div>

@if($uwagi)
    <div class="paragraf">
        <h2>§ 3. Uwagi</h2>
        <p>{{ $uwagi }}</p>
    </div>
@endif

<table class="podpisy">
    <tr>
        <td><div class="linia">podpis pracodawcy</div></td>
        <td><div class="linia">podpis pracownika</div></td>
    </tr>
</table>

<div class="stopka">Dokument wygenerowany z systemu HRM {{ $wygenerowano }}.</div>
</body>
</html>
