<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Installment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $materiales = $this->input('materiales');

        if (is_array($materiales)) {
            $materiales = array_values(array_filter(
                $materiales,
                fn ($fila) => is_array($fila) && filled($fila['material_id'] ?? null)
            ));

            foreach ($materiales as &$fila) {
                $fila['material_id'] = (int) ($fila['material_id'] ?? 0) ?: null;
                $fila['cantidad'] = $this->parseNumber($fila['cantidad'] ?? null);
                $fila['precio_unitario'] = $this->parseNumber($fila['precio_unitario'] ?? null);
            }
            unset($fila);

            $this->merge(['materiales' => $materiales]);
        }

        $this->merge([
            'monto' => $this->parseNumber($this->input('monto')) ?? 0.0,
            'monto_mora' => $this->parseNumber($this->input('monto_mora')) ?? 0.0,
            'fecha_pago' => $this->input('fecha_pago') ?: now()->toDateString(),
            'metodo' => $this->input('metodo') ?: PaymentMethod::Efectivo->value,
            'referencia' => filled($this->input('referencia')) ? trim((string) $this->input('referencia')) : null,
            'notas' => filled($this->input('notas')) ? trim((string) $this->input('notas')) : null,
            'installment_id' => $this->integer('installment_id') ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'monto_mora' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'materiales' => ['nullable', 'array'],
            'materiales.*' => ['array'],
            'materiales.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'materiales.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'materiales.*.precio_unitario' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'metodo' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'referencia' => ['nullable', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:500'],
            'installment_id' => ['nullable', 'integer', 'exists:installments,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $monto = (float) $this->input('monto');
            $materiales = $this->input('materiales', []);

            if ($monto <= 0 && (! is_array($materiales) || $materiales === [])) {
                $validator->errors()->add('monto', 'Ingresa un monto en dinero o agrega al menos un material.');
            }

            $installmentId = $this->input('installment_id');

            if (! $installmentId) {
                return;
            }

            $installment = Installment::query()->find($installmentId);
            $receivable = $this->route('receivable');

            if ($installment && $receivable && (int) $installment->receivable_id !== (int) $receivable->id) {
                $validator->errors()->add('installment_id', 'La cuota seleccionada no pertenece a este contrato.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monto.required' => 'Ingresa el monto en dinero o agrega materiales.',
            'monto_mora.min' => 'El monto para mora no puede ser negativo.',
            'materiales.*.material_id.required' => 'Selecciona el material.',
            'materiales.*.material_id.exists' => 'El material seleccionado no existe.',
            'materiales.*.cantidad.required' => 'Ingresa la cantidad del material.',
            'materiales.*.cantidad.min' => 'La cantidad debe ser mayor que cero.',
            'materiales.*.precio_unitario.required' => 'Ingresa el precio unitario.',
            'fecha_pago.before_or_equal' => 'La fecha del abono no puede ser futura.',
            'metodo.required' => 'Selecciona el método de pago.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monto' => 'monto',
            'monto_mora' => 'monto para mora',
            'materiales' => 'materiales',
            'fecha_pago' => 'fecha de pago',
            'metodo' => 'método de pago',
            'referencia' => 'referencia',
            'notas' => 'notas',
            'installment_id' => 'cuota',
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
