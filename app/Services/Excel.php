<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

class Excel
{
    /**
     * Returns cells coordinates from A to BZ
     *
     * @param int $startsFrom Represent ASCII decimal code
     * @return Generator
     */
    public function cellCoordinatesGenerator(int $startsFrom = 65): Generator
    {
        $indicators = [$startsFrom];

        while (true) {
            yield implode('', array_map('chr', $indicators));

            if (1 === count($indicators)) {
                $indicators[0]++;
            }

            if (2 === count($indicators)) {
                $indicators[1]++;
            }

            if (1 === count($indicators) && $indicators[0] > 90) {
                $indicators = [65, 65];
            }

            if (2 === count($indicators) && $indicators[1] > 90) {
                $indicators = [66, 65];
            }
        }
    }

    /**
     * Returns rows reserved for workers data
     *
     * @param int $startRow
     * @return Generator
     */
    public function workerRowsGenerator(int $startRow = 1): Generator
    {
        $cursor = $startRow;

        while (true) {
            yield [
                'work_hours' => $cursor,
                'work_time' => $cursor + 1,
                'work_paid' => $cursor + 2,
            ];

            $cursor += 3;
        }
    }
}