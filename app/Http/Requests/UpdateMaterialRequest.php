<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends StoreMaterialRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $material = $this->route('material');
        $id = is_object($material) ? $material->id : $material;

        return [
            ...parent::rules(),
            'codigo' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('materials', 'codigo')->ignore($id),
            ],
        ];
    }
}
