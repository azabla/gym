<div class="flex justify-between items-center mb-4">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            🎁 Additional Services
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Enhance the membership with optional add-ons
        </p>
    </div>

    @if ($count > 0)
        <div
            class="bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-3 py-1 rounded-full text-sm font-medium">
            {{ $count }} selected
        </div>
    @endif
</div>
