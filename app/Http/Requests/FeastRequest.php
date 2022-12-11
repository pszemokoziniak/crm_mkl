<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class FeastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id'                    =>'nullable',
            'country_id'            =>'required|int',
            'name'                  =>'required|max:255',
            'date'                  =>'required|date',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'date' => new Carbon($this->date),
        ]);
    }
}

