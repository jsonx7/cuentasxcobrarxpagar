<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPayablePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'monto' => $this->parseNumber($this->input('monto')),
            'fecha_pago' => $this->input('fecha_pago') ?: now()->toDateString(),
            'metodo' => $this->input('metodo') ?: PaymentMethod::Efectivo->value,
            'referencia' => filled($this->input('referencia')) ? trim((string) $this->input('referencia')) : null,
            'notas' => filled($this->input('notas')) ? trim((string) $this->input('notas')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'metodo' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'referencia' => ['nullable', 'string', 'max:80'],
            'notas' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monto.required' => 'El monto del pago es obligatorio.',
            'monto.min' => 'El pago debe ser mayor que cero.',
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
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
            'fecha_pago' => 'fecha de pago',
            'metodo' => 'método de pago',
            'referencia' => 'referencia',
            'notas' => 'notas',
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
