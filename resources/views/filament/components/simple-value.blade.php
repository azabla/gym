@php
    $label = $get('label') ?? ($label ?? 'Label');
    $value = is_callable($value) ? $value($get) : $value ?? '0';
    $suffix = $get('suffix') ?? ($suffix ?? '');
@endphp

<div class="text-right">
    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
        {{ $label }}
    </p>
    <p class="text-base font-semibold text-gray-900 dark:text-white mt-1">
        {{ $value }} @if ($suffix)
            <span class="text-sm text-gray-500">{{ $suffix }}</span>
        @endif
    </p>
</div>
