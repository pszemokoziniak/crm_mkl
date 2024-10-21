<?php

namespace App\Services;

use App\Models\Prognoza;

class PrognozaService
{
    public function getPrognozas($building, $year, $month, $startDate, $endDate)
    {
        return Prognoza::with('prognozadates')
//            ->when($building && $building != 'all', function ($query) use ($building) {
//                $query->where('organization_id', $building);
//            })
            ->when($building !== 'all', function ($query) use ($building) {
                $query->where('organization_id', $building);
            })
            ->when($year, function ($query) use ($year) {
                $query->whereHas('prognozadates', function ($query) use ($year) {
                    $query->where('year', $year);
                });
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereHas('prognozadates', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                });
            })
            ->get();
    }
}

