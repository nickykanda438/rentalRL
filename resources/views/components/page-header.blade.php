@props(['title', 'subtitle' => null, 'small' => null])
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $title }}
            @if ($small)<span class="ml-1 text-sm font-medium text-gray-500">{{ $small }}</span>@endif
        </h1>
        @if ($subtitle)<p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>@endif
    </div>
    <div class="flex flex-wrap items-center gap-2.5">{{ $slot }}</div>
</div>
