@php
    $package = $get('package') ?? $package;
    $addons = $get('addons') ?? $addons;
    $currency = $get('currency') ?? 'ETB';

    // Convert addons to collection if it's an array
if (is_array($addons)) {
    $addons = collect($addons);
}

$packagePrice = $package?->price ?? 0;
$addonsTotal = $addons->sum('price');
    $total = $packagePrice + $addonsTotal;
    $hasPackage = !is_null($package);
    $hasAddons = $addons->isNotEmpty();
@endphp

<div class="bg-white dark:bg-gray-900 rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-700">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3">
        <h3 class="text-white font-semibold flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            Order Summary
        </h3>
    </div>

    <div class="p-4 space-y-4">
        {{-- Package Section --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span>MEMBERSHIP PLAN</span>
            </div>

            @if ($hasPackage)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $package->name }}</div>
                            @if ($package->duration_value)
                                <div class="text-xs text-gray-500 mt-1">{{ $package->duration_value }}
                                    {{ ucfirst($package->duration_unit) }}(s)</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-primary-600">{{ number_format($packagePrice, 2) }}</div>
                            <div class="text-xs text-gray-500">{{ $currency }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-center text-gray-400">
                    No package selected
                </div>
            @endif
        </div>

        {{-- Add-ons Section --}}
        <div class="space-y-2">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>ADD-ONS</span>
                @if ($hasAddons)
                    <span
                        class="bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">{{ $addons->count() }}</span>
                @endif
            </div>

            @if ($hasAddons)
                <div class="space-y-2">
                    @foreach ($addons as $addon)
                        <div class="flex justify-between items-center pl-3 py-1">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span
                                    class="text-sm text-gray-700 dark:text-gray-300">{{ is_array($addon) ? $addon['name'] : $addon->name }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">+
                                {{ number_format(is_array($addon) ? $addon['price'] : $addon->price, 2) }}</span>
                        </div>
                    @endforeach

                    @if ($addonsTotal > 0)
                        <div class="border-t border-gray-200 dark:border-gray-700 mt-2 pt-2">
                            <div class="flex justify-between items-center pl-3 text-sm">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="font-medium">{{ number_format($addonsTotal, 2) }}
                                    {{ $currency }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center text-gray-400 text-sm py-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    No add-ons selected
                </div>
            @endif
        </div>

        {{-- Divider --}}
        <div class="border-t-2 border-dashed border-gray-200 dark:border-gray-700"></div>

        {{-- Total --}}
        <div
            class="flex justify-between items-center p-3 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                    </path>
                </svg>
                <span class="font-bold text-gray-900 dark:text-white">TOTAL AMOUNT</span>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-primary-600">{{ number_format($total, 2) }}</div>
                <div class="text-xs text-gray-500">{{ $currency }}</div>
            </div>
        </div>
    </div>
</div>
