<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $dui = preg_replace('/\D/', '', (string) $this->input('dui'));

        if (strlen($dui) === 9) {
            $dui = substr($dui, 0, 8).'-'.substr($dui, 8, 1);
        }

        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'dui' => filled($dui) ? $dui : null,
            'nit' => filled($this->input('nit')) ? trim((string) $this->input('nit')) : null,
            'telefono' => filled($this->input('telefono')) ? trim((string) $this->input('telefono')) : null,
            'email' => filled($this->input('email')) ? trim((string) $this->input('email')) : null,
            'direccion' => filled($this->input('direccion')) ? trim((string) $this->input('direccion')) : null,
            'notas' => filled($this->input('notas')) ? trim((string) $this->input('notas')) : null,
        ]);

        if ($this->has('activo')) {
            $this->merge(['activo' => $this->boolean('activo')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:160'],
            'dui' => ['nullable', 'string', 'regex:/^\d{8}-\d$/', 'unique:clients,dui'],
            'nit' => ['nullable', 'string', 'max:20', 'unique:clients,nit'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'dui.regex' => 'El DUI debe tener el formato 00000000-0.',
            'dui.unique' => 'Ya existe un cliente con este DUI.',
            'nit.unique' => 'Ya existe un cliente con este NIT.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'dui' => 'DUI',
            'nit' => 'NIT',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'direccion' => 'dirección',
            'notas' => 'notas',
            'activo' => 'activo',
        ];
    }
}
