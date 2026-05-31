@php

    $addons = $get('addons') ?? ($addons ?? []);
    $addonTotal = $get('addonTotal') ?? ($addonTotal ?? 0);
    $packageTotal = $get('packageTotal') ?? ($packageTotal ?? 0);
    // dump($packageTotal);
    $grandTotal = $get('grandTotal') ?? ($grandTotal ?? 0);
    $currency = $get('currency') ?? ($currency ?? 'ETB');
@endphp

<div class="space-y-4 p-2">
    {{-- Package price --}}
    <div class="flex justify-between items-center pb-2 border-b border-gray-200 dark:border-gray-700">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Base Package</span>
        <span class="text-base font-semibold">{{ number_format($packageTotal, 2) }} {{ $currency }}</span>
    </div>

    {{-- Add-ons list --}}
    @if (count($addons) > 0)
        <div class="space-y-2">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Selected Add-ons
            </div>
            @foreach ($addons as $addon)
                <div class="flex justify-between items-center text-sm">
                    <div class="flex items-center gap-2">
                        <span class="text-primary-500">✓</span>
                        <span>{{ $addon['name'] }}</span>
                    </div>
                    <span class="font-mono text-sm">{{ number_format($addon['price'], 2) }} {{ $currency }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-4 text-gray-400 text-sm">
            No add-ons selected
        </div>
    @endif

    {{-- Total --}}
    <div class="pt-3 mt-2 border-t-2 border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center">
            <span class="text-base font-bold text-gray-900 dark:text-white">Total Amount</span>
            <div class="text-right">
                <span class="text-2xl font-bold text-primary-600">{{ number_format($grandTotal, 2) }}</span>
                <span class="text-sm text-gray-500 ml-1">{{ $currency }}</span>
            </div>
        </div>

        @if ($addonTotal > 0)
            <div class="text-xs text-gray-500 mt-1 text-right">
                (Including {{ number_format($addonTotal, 2) }} {{ $currency }} for add-ons)
            </div>
        @endif
    </div>
</div>
