@props([
    'value',
    'decimals' => 2,
])

{{ '$'.number_format((float) $value, (int) $decimals) }}
