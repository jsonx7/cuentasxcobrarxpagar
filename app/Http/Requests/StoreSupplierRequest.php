<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'nit' => filled($this->input('nit')) ? trim((string) $this->input('nit')) : null,
            'contacto' => filled($this->input('contacto')) ? trim((string) $this->input('contacto')) : null,
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
            'nit' => ['nullable', 'string', 'max:20', 'unique:suppliers,nit'],
            'contacto' => ['nullable', 'string', 'max:120'],
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
            'nombre.required' => 'El nombre del proveedor es obligatorio.',
            'nit.unique' => 'Ya existe un proveedor con este NIT.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'nit' => 'NIT',
            'contacto' => 'contacto',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'direccion' => 'dirección',
            'notas' => 'notas',
            'activo' => 'activo',
        ];
    }
}
