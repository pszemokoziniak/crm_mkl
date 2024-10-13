<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PrognozaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Ensure that 'prognozadates' is loaded and is a collection
        $prognozadates = $this->whenLoaded('prognozadates');

        $sumWorkers = array_reduce($this->prognozadates, function ($carry, $item) {
            return $carry + $item['workers_count'];
        }, 0);

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'start' => Carbon::parse($this->prognozadates->start ?? now())->format('Y-m-d'),
            'end' => Carbon::parse($this->prognozadates->end ?? now())->format('Y-m-d'),
            'total_workers_count' => $sumWorkers,
        ];
    }
}
