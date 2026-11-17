<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends StoreSupplierRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id = is_object($supplier) ? $supplier->id : $supplier;

        return [
            ...parent::rules(),
            'nit' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'nit')->ignore($id),
            ],
        ];
    }
}
