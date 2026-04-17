<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="searchPatient" class="space-y-3">
            {{ $this->form }}
            <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-lg border border-transparent bg-primary-600 px-4 py-2 text-center font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                Search Patient History
            </button>
        </form>

        @if($hasSearched)
            @if(!empty($patientData))
                <div class="overflow-x-auto rounded-lg border border-gray-300 bg-white shadow-sm">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Lab ID</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Patient #</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Phone</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Age</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Gender</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Address</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Consultant</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Total Bill</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Paid Bill</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Due Bill</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Discount</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 whitespace-nowrap">Date</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700 whitespace-nowrap">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($patientData as $patient)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['labid'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['patientNumber'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['name'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['phone'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['age'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['gender'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['address'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['consultant'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['totalBill'] ?? '0' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['paidBill'] ?? '0' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['dueBill'] ?? '0' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $patient['discount'] ?? '0' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        @if($patient['date'])
                                            {{ \Carbon\Carbon::parse($patient['date'])->format('M d, Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="https://reports.pathcaresoft.com/Report/generate-report?userId={{ urlencode($patient['patientNumber']) }}&password={{ urlencode($patient['password']) }}" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center justify-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Download
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <p class="text-center text-yellow-800">No patient history found for this phone number.</p>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
