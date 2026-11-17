<?php

namespace App\Http\Requests;

use App\Enums\PaymentFrequency;
use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $frecuencia = (string) $this->input('frecuencia', PaymentFrequency::Semanal->value);

        $this->merge([
            'client_id' => $this->integer('client_id') ?: null,
            'monto' => $this->parseNumber($this->input('monto')),
            'tasa_interes_anual' => $this->parseNumber($this->input('tasa_interes_anual'))
                ?? (float) config('cuentas.tasa_interes_default', 8),
            'tasa_mora_mensual' => $this->parseNumber($this->input('tasa_mora_mensual'))
                ?? (float) config('cuentas.tasa_mora_default', 5),
            'plazo' => $frecuencia === PaymentFrequency::Unico->value ? 1 : $this->input('plazo'),
            'frecuencia' => $frecuencia,
            'fecha_desembolso' => $this->input('fecha_desembolso') ?: now()->toDateString(),
            'observaciones' => filled($this->input('observaciones')) ? trim((string) $this->input('observaciones')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'monto' => ['required', 'numeric', 'min:1', 'max:10000000'],
            'tasa_interes_anual' => ['required', 'numeric', 'min:0', 'max:100'],
            'tasa_mora_mensual' => ['required', 'numeric', 'min:0', 'max:100'],
            'plazo' => [
                'nullable',
                'integer',
                'min:1',
                'max:520',
                Rule::requiredIf(fn () => $this->input('frecuencia') !== PaymentFrequency::Unico->value),
            ],
            'frecuencia' => ['required', Rule::in(array_keys(PaymentFrequency::createOptions()))],
            'fecha_desembolso' => ['required', 'date'],
            'fecha_primer_pago' => ['required', 'date', 'after_or_equal:fecha_desembolso'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $client = Client::query()->find($this->integer('client_id'));

            if ($client && ! $client->activo) {
                $validator->errors()->add('client_id', 'El cliente está inactivo. Actívalo antes de crear un contrato.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Selecciona un cliente.',
            'client_id.exists' => 'El cliente seleccionado no existe.',
            'monto.required' => 'El monto del contrato es obligatorio.',
            'monto.min' => 'El monto debe ser mayor que cero.',
            'plazo.required' => 'Indica el número de cuotas.',
            'plazo.min' => 'El plazo debe tener al menos una cuota.',
            'fecha_primer_pago.after_or_equal' => 'La primera fecha de pago no puede ser anterior al desembolso.',
            'tasa_interes_anual.max' => 'La tasa de interés no puede superar el 100%.',
            'tasa_mora_mensual.max' => 'La tasa de mora no puede superar el 100%.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'monto' => 'monto',
            'tasa_interes_anual' => 'tasa de interés',
            'tasa_mora_mensual' => 'tasa de mora mensual',
            'plazo' => 'número de cuotas',
            'frecuencia' => 'frecuencia de pago',
            'fecha_desembolso' => 'fecha de desembolso',
            'fecha_primer_pago' => 'primera fecha de pago',
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
