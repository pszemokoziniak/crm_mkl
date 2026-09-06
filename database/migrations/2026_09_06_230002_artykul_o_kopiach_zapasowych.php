<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ArtykulOKopiachZapasowych extends Migration
{
    private const TYTUL = 'Kopie zapasowe HRM — jak działają i jak odtworzyć';

    /**
     * Pierwszy artykuł bazy wiedzy. Instrukcja odtwarzania musi być dostępna
     * wtedy, gdy coś już padło — czyli nie w mailu i nie na czacie.
     * Oznaczona jako tylko dla admina: są w niej ścieżki na serwerze
     * i polecenia, których reszta firmy nie potrzebuje.
     *
     * @return void
     */
    public function up()
    {
        if (DB::table('baza_wiedzy')->where('tytul', self::TYTUL)->exists()) {
            return;
        }

        DB::table('baza_wiedzy')->insert([
            'tytul' => self::TYTUL,
            'kategoria' => 'Administracja',
            'tylko_admin' => true,
            'kolejnosc' => 0,
            'tresc' => $this->tresc(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return void
     */
    public function down()
    {
        DB::table('baza_wiedzy')->where('tytul', self::TYTUL)->delete();
    }

    private function tresc(): string
    {
        return <<<'MD'
## Co dzieje się codziennie

- O **2:30 w nocy** serwer sam robi kopię: całą bazę danych i wszystkie skany oraz dokumenty pracowników.
- Kopia zostaje na serwerze (baza — 30 ostatnich dni, skany — 14 dni) i dodatkowo leci do chmury **Backblaze B2**.
- Do chmury trafia **zaszyfrowana**. Backblaze nie widzi ani nazwisk, ani zawartości, ani nawet nazw plików.
- Kopii w chmurze **przez 30 dni nie da się skasować** — ani nam, ani komuś, kto włamie się na serwer. To zabezpieczenie na wypadek ransomware.
- Jeśli wysyłka nie uda się przez 3 dni z rzędu, w logu pojawia się ostrzeżenie.

## Gdzie co leży

| Co | Gdzie |
| --- | --- |
| kopie na serwerze | `/var/backups/hrm` |
| kopia w chmurze | Backblaze B2, kubełek `mkl-hrm-backup` |
| dziennik | `/var/log/hrm-backup.log` |
| skrypt | `/usr/local/sbin/hrm-backup.sh` |
| pełna instrukcja na serwerze | `/var/backups/hrm/JAK-ODTWORZYC.txt` |

## Ważne: hasło szyfrujące

Dane są szyfrowane naszym hasłem, **zanim** opuszczą serwer. Bez tego hasła kopia z chmury jest bezużyteczna — nie odzyska jej ani MKL, ani Backblaze. Hasło musi być zapisane w menedżerze haseł, **poza serwerem**.

---

# Jak odtworzyć

Najpierw sprawdź, z czego:

```
ssh mkl
ls -l /var/backups/hrm/baza/
```

## 1. Baza danych

Na przykład gdy ktoś skasował dane. Odtwarzamy **najpierw do osobnej bazy**, żeby sprawdzić przed nadpisaniem działającej:

```
mysql -e "CREATE DATABASE mklDB_odtworzone CHARACTER SET utf8mb4"
zcat /var/backups/hrm/baza/mklDB-RRRR-MM-DD.sql.gz | mysql mklDB_odtworzone
```

Obejrzyj dane w `mklDB_odtworzone`. Dopiero gdy się zgadzają:

```
zcat /var/backups/hrm/baza/mklDB-RRRR-MM-DD.sql.gz | mysql mklDB
```

## 2. Skany i dokumenty

```
rsync -a /var/backups/hrm/skany/RRRR-MM-DD/ /var/www/mkl/storage/app/
chown -R www-data:www-data /var/www/mkl/storage/app
```

Pojedynczy plik — po prostu skopiuj go z katalogu z odpowiednią datą.

## 3. Gdy przepadł cały serwer — bierzemy z chmury

```
rclone ls hrm-b2-crypt:baza
rclone copy hrm-b2-crypt:baza/mklDB-RRRR-MM-DD.sql.gz /root/
rclone sync hrm-b2-crypt:skany/ /var/www/mkl/storage/app/
```

Na nowym serwerze trzeba najpierw wpisać hasło szyfrujące:

```
/usr/local/sbin/hrm-b2-konfiguruj.sh
```

---

*Sprawdzone 6.09.2026: odtworzenie bazy i skanów prosto z chmury przechodzi w całości — liczba rekordów zgadza się co do jednego, polskie znaki zachowane, pliki identyczne co do sumy kontrolnej.*
MD;
    }
}
