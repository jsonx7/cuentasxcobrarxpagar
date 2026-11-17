<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateClientRequest extends StoreClientRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $client = $this->route('client');
        $id = is_object($client) ? $client->id : $client;

        return [
            ...parent::rules(),
            'dui' => [
                'nullable',
                'string',
                'regex:/^\d{8}-\d$/',
                Rule::unique('clients', 'dui')->ignore($id),
            ],
            'nit' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clients', 'nit')->ignore($id),
            ],
        ];
    }
}
