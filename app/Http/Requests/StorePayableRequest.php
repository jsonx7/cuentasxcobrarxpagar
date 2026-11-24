<?php

namespace App\Http\Requests;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'codigo' => filled($this->input('codigo'))
                ? strtoupper(trim((string) $this->input('codigo')))
                : null,
            'concepto' => trim((string) $this->input('concepto')),
            'monto' => $this->parseNumber($this->input('monto')),
            'fecha_emision' => $this->input('fecha_emision') ?: now()->toDateString(),
            'fecha_primer_pago' => $this->input('fecha_primer_pago') ?: $this->input('fecha_vencimiento'),
            'fecha_vencimiento' => $this->input('fecha_primer_pago') ?: $this->input('fecha_vencimiento'),
            'tiene_prima' => $this->boolean('tiene_prima') ? 1 : 0,
            'monto_prima' => $this->parseNumber($this->input('monto_prima')),
            'fecha_prima' => $this->input('fecha_prima') ?: null,
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
            'codigo' => ['nullable', 'string', 'max:30', Rule::unique('payables', 'codigo')],
            'concepto' => ['required', 'string', 'min:3', 'max:160'],
            'monto' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'fecha_primer_pago' => ['required', 'date', 'after_or_equal:fecha_emision'],
            'tiene_prima' => ['required', 'boolean'],
            'monto_prima' => ['exclude_unless:tiene_prima,1', 'required', 'numeric', 'min:0.01', 'max:10000000', 'lte:monto'],
            'fecha_prima' => ['exclude_unless:tiene_prima,1', 'required', 'date', 'after_or_equal:fecha_emision', 'before_or_equal:today'],
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
            'codigo.unique' => 'Ese código ya está registrado. Déjalo vacío para generarlo automáticamente.',
            'concepto.required' => 'El concepto de la obligación es obligatorio.',
            'monto.required' => 'El total de la deuda es obligatorio.',
            'monto.min' => 'El total de la deuda debe ser mayor que cero.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser anterior a la emisión.',
            'fecha_primer_pago.required' => 'Indica la primera fecha de pago.',
            'fecha_primer_pago.after_or_equal' => 'La primera fecha de pago no puede ser anterior a la emisión.',
            'monto_prima.required' => 'Indica el monto de la prima abonada.',
            'monto_prima.min' => 'El monto de la prima debe ser mayor que cero.',
            'monto_prima.lte' => 'La prima no puede ser mayor que el monto de la obligación.',
            'fecha_prima.required' => 'Indica la fecha en que se abonó la prima.',
            'fecha_prima.after_or_equal' => 'La fecha de la prima no puede ser anterior a la emisión.',
            'fecha_prima.before_or_equal' => 'La fecha de la prima no puede ser futura.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'codigo' => 'código',
            'concepto' => 'concepto',
            'monto' => 'total de la deuda',
            'fecha_emision' => 'fecha de emisión',
            'fecha_vencimiento' => 'fecha de vencimiento',
            'fecha_primer_pago' => 'primera fecha de pago',
            'tiene_prima' => 'prima abonada',
            'monto_prima' => 'monto de la prima',
            'fecha_prima' => 'fecha de la prima',
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
