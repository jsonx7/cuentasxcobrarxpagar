<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'codigo' => filled($this->input('codigo')) ? trim((string) $this->input('codigo')) : null,
            'unidad' => filled($this->input('unidad'))
                ? trim((string) $this->input('unidad'))
                : (string) config('cuentas.unidad_material_default', 'unidad'),
            'precio' => $this->parseNumber($this->input('precio')),
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
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'codigo' => ['nullable', 'string', 'max:40', 'unique:materials,codigo'],
            'unidad' => ['required', 'string', 'max:30'],
            'precio' => ['required', 'numeric', 'min:0', 'max:10000000'],
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
            'nombre.required' => 'El nombre del material es obligatorio.',
            'codigo.unique' => 'Ya existe un material con este código.',
            'precio.required' => 'El precio del material es obligatorio.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'codigo' => 'código',
            'unidad' => 'unidad',
            'precio' => 'precio',
            'notas' => 'notas',
            'activo' => 'activo',
        ];
    }

    protected function parseNumber(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([',', '$', ' ', "\u{00A0}"], '', (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
