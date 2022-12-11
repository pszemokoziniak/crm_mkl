<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FestDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id'            =>'required|int',
            'name'                  =>'required|max:255',
            'fest_date'             =>'required|date',
        ];
    }
}
