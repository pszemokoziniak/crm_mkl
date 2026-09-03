{{-- Szablon umowy/aneksu. Ten sam plik obsługuje podgląd do druku i plik .doc,
     dlatego układ opiera się na tabelach i prostym CSS — Word nie rozumie
     flexboxa ani gridu, a musi otworzyć ten dokument bez rozjazdów. --}}
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>{{ $tytul }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }

        body {
            font-family: "Times New Roman", Georgia, serif;
            font-size: 11.5pt;
            line-height: 1.55;
            color: #111;
            margin: 0;
        }

        .arkusz { max-width: 180mm; margin: 0 auto; padding: 10mm 0; }

        /* Nagłówek firmowy */
        .firma { width: 100%; border-collapse: collapse; margin-bottom: 7mm; }
        .firma td { vertical-align: bottom; padding: 0 0 2.5mm; border-bottom: 2px solid #1f2a44; }
        .firma .nazwa {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15pt; font-weight: bold; letter-spacing: 1px; color: #1f2a44;
        }
        .firma .podpis-firmy { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #555; letter-spacing: .5px; }
        .firma .data { text-align: right; font-size: 10.5pt; color: #333; white-space: nowrap; }

        /* Tytuł dokumentu */
        h1 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15pt; font-weight: bold; letter-spacing: 2px;
            text-align: center; text-transform: uppercase;
            margin: 9mm 0 2mm;
        }
        .podtytul { text-align: center; font-size: 10pt; color: #666; margin-bottom: 8mm; }

        /* Strony umowy */
        .strony { width: 100%; border-collapse: collapse; margin-bottom: 7mm; }
        .strony td { width: 50%; vertical-align: top; padding: 3mm 4mm; border: 1px solid #d5d9e2; background: #fafbfc; }
        .strony .rola {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt; letter-spacing: 1px; text-transform: uppercase; color: #6b7280;
            display: block; margin-bottom: 1.5mm;
        }
        .strony .kto { font-size: 12pt; font-weight: bold; }
        .strony .szczegol { font-size: 10pt; color: #444; }

        /* Paragrafy */
        .paragraf { margin-bottom: 6mm; }
        .paragraf h2 {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt; letter-spacing: 1px; text-transform: uppercase; color: #1f2a44;
            margin: 0 0 2mm; padding-bottom: 1mm; border-bottom: 1px solid #d5d9e2;
        }
        .paragraf p { margin: 0 0 2mm; text-align: justify; }

        /* Tabela warunków */
        table.dane { width: 100%; border-collapse: collapse; margin: 3mm 0 0; }
        table.dane td { padding: 2.5mm 3mm; border-bottom: 1px solid #e3e6ec; vertical-align: top; }
        table.dane tr:first-child td { border-top: 1px solid #e3e6ec; }
        table.dane td.etykieta {
            width: 45%;
            font-family: Arial, Helvetica, sans-serif; font-size: 9.5pt; color: #6b7280;
        }
        table.dane td.wartosc { font-weight: bold; }

        /* Podpisy */
        .podpisy { width: 100%; margin-top: 18mm; border-collapse: collapse; }
        .podpisy td { width: 50%; text-align: center; padding: 0 6mm; vertical-align: bottom; }
        .podpisy .linia {
            border-top: 1px dotted #333; padding-top: 2mm;
            font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; letter-spacing: .5px; color: #555;
        }

        .pasek-druku {
            position: fixed; top: 0; left: 0; right: 0;
            background: #1f2a44; color: #fff; padding: 8px 16px; text-align: center;
            font-family: Arial, Helvetica, sans-serif; font-size: 10pt;
        }
        .pasek-druku button {
            margin-left: 12px; padding: 5px 14px; border: 0; border-radius: 4px;
            background: #fff; color: #1f2a44; font-weight: bold; cursor: pointer;
        }
        .z-paskiem { padding-top: 46px; }

        @media print {
            .pasek-druku { display: none; }
            .z-paskiem { padding-top: 0; }
            .arkusz { padding: 0; max-width: none; }
            .paragraf { page-break-inside: avoid; }
            .podpisy { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="{{ $pokazPasek ? 'z-paskiem' : '' }}">

@if($pokazPasek)
    <div class="pasek-druku">
        Podgląd dokumentu — zapisz jako PDF przez okno drukowania
        <button type="button" onclick="window.print()">Drukuj / Zapisz PDF</button>
    </div>
@endif

<div class="arkusz">
    <table class="firma">
        <tr>
            <td>
                {{-- Logo jako dane w dokumencie; bez pliku zostaje sama nazwa. --}}
                @if($logo)
                    <img src="{{ $logo }}" width="170" height="40" alt="{{ $pracodawca }}" style="display:block; margin-bottom:1.5mm;">
                    <span class="podpis-firmy">Dokumentacja kadrowa</span>
                @else
                    <span class="nazwa">{{ $pracodawca }}</span><br>
                    <span class="podpis-firmy">Dokumentacja kadrowa</span>
                @endif
            </td>
            <td class="data">{{ $miejsce }}, dnia {{ $dataZawarcia }}</td>
        </tr>
    </table>

    <h1>{{ $tytul }}</h1>
    <div class="podtytul">{{ $wstep }}</div>

    <table class="strony">
        <tr>
            <td>
                <span class="rola">Pracodawca</span>
                <span class="kto">{{ $pracodawca }}</span>
            </td>
            <td>
                <span class="rola">Pracownik</span>
                <span class="kto">{{ $pracownik['imie_nazwisko'] }}</span><br>
                @if($pracownik['pesel'])
                    <span class="szczegol">PESEL {{ $pracownik['pesel'] }}</span><br>
                @endif
                @if($pracownik['adres'])
                    <span class="szczegol">{{ $pracownik['adres'] }}</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="paragraf">
        <h2>§ 1. Warunki zatrudnienia</h2>
        <table class="dane">
            <tr><td class="etykieta">Stanowisko</td><td class="wartosc">{{ $stanowisko ?: '—' }}</td></tr>
            <tr><td class="etykieta">Miejsce wykonywania pracy (budowa)</td><td class="wartosc">{{ $budowa ?: '—' }}</td></tr>
            <tr><td class="etykieta">Okres</td><td class="wartosc">od {{ $od }} do {{ $do }}</td></tr>
            @if($wynagrodzenie)
                <tr><td class="etykieta">Wynagrodzenie</td><td class="wartosc">{{ $wynagrodzenie }}</td></tr>
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

</div>
</body>
</html>
