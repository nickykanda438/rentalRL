@props(['footer' => null, 'title' => null])
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs']) }}>
    @if ($title)<h2 class="px-5 pt-5 pb-3 text-base font-bold text-gray-900">{{ $title }}</h2>@endif
    <div class="overflow-x-auto">
        <table class="w-full min-w-3xl whitespace-nowrap text-left text-sm text-gray-700">{{ $slot }}</table>
    </div>
    @if ($footer)
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-3 text-sm text-gray-500">{{ $footer }}</div>
    @endif
</section>
