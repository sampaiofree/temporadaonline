@props(['title', 'icon', 'active' => false])

@php
    $groupKey = 'sidebar-group-' . (\Illuminate\Support\Str::slug($title) ?: md5($title));
@endphp

<div
    x-data="{
        open: {{ $active ? 'true' : 'false' }},
        groupKey: @js($groupKey),
        toggle() {
            const shouldOpen = !this.open;
            if (shouldOpen) {
                window.dispatchEvent(new CustomEvent('admin-sidebar:open', { detail: { groupKey: this.groupKey } }));
            }
            this.open = shouldOpen;
        }
    }"
    @admin-sidebar:open.window="if ($event.detail.groupKey !== groupKey) { open = false }"
    class="space-y-1"
>
    <button
        @click="toggle()"
        type="button"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100"
        :class="{ 'bg-slate-50 text-blue-600': open }"
    >
        <div class="flex items-center gap-3">
            <i data-lucide="{{ $icon }}" class="h-4 w-4 text-slate-400" :class="{ 'text-blue-600': open }"></i>
            <span>{{ $title }}</span>
        </div>
        <i
            data-lucide="chevron-right"
            class="h-4 w-4 text-slate-400 transition-transform duration-200"
            :class="open ? 'rotate-90 text-blue-600' : ''"
        ></i>
    </button>

    <div x-show="open" x-cloak class="my-1 ml-4 space-y-1 border-l border-slate-200 pl-4">
        {{ $slot }}
    </div>
</div>
