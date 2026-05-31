@php
    $title = $get('title') ?? ($title ?? 'Title');
    $value = is_callable($value) ? $value($get) : $value ?? '0';
    $currency = $get('currency') ?? ($currency ?? '');
    $subtext = is_callable($subtext) ? $subtext($get) : $subtext ?? '';
    $color = $get('color') ?? ($color ?? 'primary');
    $icon = $get('icon') ?? ($icon ?? 'heroicon-o-information-circle');

    $colorClasses =
        [
            'primary' => 'bg-primary-50 dark:bg-primary-900/20 border-primary-200 dark:border-primary-800',
            'success' => 'bg-success-50 dark:bg-success-900/20 border-success-200 dark:border-success-800',
            'warning' => 'bg-warning-50 dark:bg-warning-900/20 border-warning-200 dark:border-warning-800',
            'danger' => 'bg-danger-50 dark:bg-danger-900/20 border-danger-200 dark:border-danger-800',
            'info' => 'bg-info-50 dark:bg-info-900/20 border-info-200 dark:border-info-800',
        ][$color] ?? 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700';

    $iconColorClasses =
        [
            'primary' => 'text-primary-600 dark:text-primary-400',
            'success' => 'text-success-600 dark:text-success-400',
            'warning' => 'text-warning-600 dark:text-warning-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'info' => 'text-info-600 dark:text-info-400',
        ][$color] ?? 'text-gray-600 dark:text-gray-400';
@endphp

<div class="{{ $colorClasses }} border rounded-lg p-4 transition-all hover:shadow-md">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                <x-filament::icon :name="$icon" class="w-5 h-5 {{ $iconColorClasses }}" />
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ $title }}
                </p>
            </div>

            <div class="mt-1">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $value }}
                </span>
                @if ($currency)
                    <span class="text-sm text-gray-500 ml-1">{{ $currency }}</span>
                @endif
            </div>

            @if ($subtext)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ $subtext }}
                </p>
            @endif
        </div>
    </div>
</div>
