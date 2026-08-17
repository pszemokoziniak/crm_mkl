<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ZadaniePriority;
use App\Enums\ZadanieStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZadanieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:20000',
            'url' => 'nullable|url|max:2048',
            'status' => ['required', Rule::in(ZadanieStatus::values())],
            'priority' => ['required', Rule::in(ZadaniePriority::values())],
            'assignee_id' => 'nullable|integer|exists:users,id',
            'deadline' => 'nullable|date',
            'screenshots' => 'nullable|array|max:10',
            'screenshots.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Pole :attribute jest wymagane.',
            'title.min' => 'Tytuł musi mieć co najmniej 3 znaki.',
            'url.url' => 'Link musi być poprawnym adresem (razem z http:// lub https://).',
            'screenshots.max' => 'Maksymalnie 10 plików na raz.',
            'screenshots.*.max' => 'Plik jest zbyt duży (max 10 MB).',
            'screenshots.*.mimes' => 'Dozwolone formaty: jpg, png, gif, webp, pdf.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'Tytuł',
            'description' => 'Opis',
            'url' => 'Link',
            'status' => 'Status',
            'priority' => 'Priorytet',
            'assignee_id' => 'Osoba przypisana',
            'deadline' => 'Termin',
            'screenshots' => 'Print screeny',
        ];
    }
}
