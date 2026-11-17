<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_id' => $this->integer('supplier_id') ?: null,
            'concepto' => trim((string) $this->input('concepto')),
            'monto' => $this->parseNumber($this->input('monto')),
            'fecha_emision' => $this->input('fecha_emision') ?: now()->toDateString(),
            'observaciones' => filled($this->input('observaciones')) ? trim((string) $this->input('observaciones')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'concepto' => ['required', 'string', 'min:3', 'max:160'],
            'monto' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $supplier = Supplier::query()->find($this->integer('supplier_id'));

            if ($supplier && ! $supplier->activo) {
                $validator->errors()->add('supplier_id', 'El proveedor está inactivo. Actívalo antes de crear una obligación.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Selecciona un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no existe.',
            'concepto.required' => 'El concepto de la obligación es obligatorio.',
            'monto.required' => 'El monto de la obligación es obligatorio.',
            'monto.min' => 'El monto debe ser mayor que cero.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser anterior a la emisión.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'concepto' => 'concepto',
            'monto' => 'monto',
            'fecha_emision' => 'fecha de emisión',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'observaciones' => 'observaciones',
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
