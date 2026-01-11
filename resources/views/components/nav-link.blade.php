@props(['active' => false, 'href'])

<a href="{{ $href }}" 
   {{ $attributes->merge(['class' => 'block py-1.5 text-sm transition-colors ' . ($active ? 'text-blue-600 font-semibold' : 'text-slate-500 hover:text-blue-600')]) }}>
    {{ $slot }}
</a>