<!DOCTYPE html>
<html lang="pl">
<head><meta charset="utf-8"><title>Twój link do HRM</title></head>
<body style="margin:0; padding:24px; background-color:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#1f2a44;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="max-width:560px; border:1px solid #d5d9e2;">
    <tr><td style="padding:24px 28px;">
        <div style="font-size:18px; font-weight:bold;">Cześć {{ $imie }},</div>
        <div style="padding-top:10px; font-size:14px; line-height:20px;">
            To Twój osobisty link do strony HRM na telefon. Możesz na niej złożyć wniosek urlopowy
            i sprawdzić, czy kierownik go zatwierdził.
        </div>
        <div style="padding:20px 0;">
            <a href="{{ $adres }}" style="display:inline-block; padding:12px 20px; background-color:#4f46e5; color:#ffffff; text-decoration:none; font-size:15px; font-weight:bold;">Otwórz moją stronę HRM</a>
        </div>
        <div style="font-size:13px; line-height:19px; color:#4b5563;">
            Przy pierwszym wejściu ustaw 4-cyfrowy PIN. Link jest tylko dla Ciebie — nie przesyłaj go dalej.
            Wygodnie jest dodać stronę do ekranu głównego telefonu (w przeglądarce: „Dodaj do ekranu początkowego”).
            Jeśli link nie działa, poproś kadry o nowy.
        </div>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
