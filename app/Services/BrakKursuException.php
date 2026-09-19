<?php

declare(strict_types=1);

namespace App\Services;

/** NBP nie ma kursu dla tej waluty i dnia (ani w 10 dniach wstecz) albo nie odpowiada. */
class BrakKursuException extends \RuntimeException
{
}
