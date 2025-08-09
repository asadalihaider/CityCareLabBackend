<div class="space-y-4">
    <div class="flex flex-col gap-4">
        <div class="w-full">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Booking Information</h3>
            <div class="mt-2 space-y-2">
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Type</p>
                    <x-filament::badge :color="$booking->booking_type->color()">
                        {{ $booking->booking_type->label() }}
                    </x-filament::badge>
                </div>
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Booking Date</p>
                    <p class="text-sm">
                        {{ $booking->booking_date?->format('M j, Y g:i A') ?? 'Not set' }}</p>
                </div>
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Status</p>
                    <x-filament::badge color="info">
                        {{ $booking->status->label() }}
                    </x-filament::badge>
                </div>
            </div>
        </div>

        <div class="w-full">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Patient Information</h3>
            <div class="mt-2 space-y-2">
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Customer</p>
                    <p class="text-sm">{{ $booking->customer?->name ?? 'N/A' }}</p>
                </div>
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Patient Name</p>
                    <p class="text-sm">{{ $booking->patient_name }}</p>
                </div>
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Contact Number</p>
                    <p class="text-sm">{{ $booking->contact_number }}</p>
                </div>
                <div class="w-full flex items-center justify-between space-x-2">
                    <p class="text-sm font-medium">Address</p>
                    <p class="text-sm">{{ $booking->address }}</p>
                </div>
            </div>
        </div>
    </div>

    @if ($booking->purpose)
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Purpose</h3>
            <p class="mt-2 text-sm">
                {{ $booking->purpose }}
            </p>
        </div>
    @endif
</div>
