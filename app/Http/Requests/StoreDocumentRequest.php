<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'max:50', 'min:4'],
            'typ' => ['required'],
            'document' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Tytuł musi mieć od 4 do 50 znaków',
            'name.max' => 'Tytuł musi mieć od 4 do 50 znaków',
            'document.mimes' => 'Tylko dokumenty pdf o wielkości do 10 MB'
        ];
    }

}
