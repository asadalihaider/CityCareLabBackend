<ul class="list space-y-2">
    @foreach($members as $member)
        <li class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $member->customer->name }}
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $member->relationship_type->label() }}
            </span>
        </li>
    @endforeach
</ul>
