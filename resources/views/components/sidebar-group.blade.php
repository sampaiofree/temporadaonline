@props(['title', 'icon', 'active' => false])

{{-- Se $active for true, o menu começa aberto --}}
<div x-data="{ open: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    <button @click="open = !open" 
        type="button"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors"
        :class="{ 'bg-slate-50 text-blue-600': open }"> {{-- Opcional: muda cor do título se aberto --}}
        <div class="flex items-center gap-3">
            <i data-lucide="{{ $icon }}" class="w-4 h-4 text-slate-400" :class="{ 'text-blue-600': open }"></i>
            <span>{{ $title }}</span>
        </div>
        <i data-lucide="chevron-right" 
           class="w-4 h-4 text-slate-400 transition-transform duration-200"
           :class="open ? 'rotate-90 text-blue-600' : ''"></i>
    </button>

    <div x-show="open" x-cloak class="ml-4 border-l border-slate-200 pl-4 space-y-1 my-1">
        {{ $slot }}
    </div>
</div>