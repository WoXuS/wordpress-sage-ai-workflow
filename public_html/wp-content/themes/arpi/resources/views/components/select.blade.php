@props(['name', 'options' => [], 'value' => null, 'labelledby' => null])
@php
  $selected = collect($options)->firstWhere('value', $value) ?? ($options[0] ?? ['value' => '', 'label' => '']);
@endphp
{{-- Custom listbox (ARIA select-only combobox). Progressive-enhancement: the
     hidden input carries the value so it posts like a native <select>; select.js
     drives open/close + keyboard. --}}
<div {{ $attributes->merge(['class' => 'c-select group relative']) }} data-select>
  <input type="hidden" name="{{ $name }}" value="{{ $selected['value'] }}" data-select-input>

  <button type="button"
          class="c-input flex w-full items-center justify-between gap-3 text-left transition-colors group-data-[open]:border-red"
          data-select-trigger
          aria-haspopup="listbox"
          aria-expanded="false"
          @if($labelledby) aria-labelledby="{{ $labelledby }}" @endif>
    <span data-select-value>{{ $selected['label'] }}</span>
    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"
         class="size-4 shrink-0 transition-transform duration-200 ease-out group-data-[open]:rotate-180">
      <path d="M5 7.5 10 12.5 15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>

  <ul role="listbox" data-select-list tabindex="-1"
      class="absolute inset-x-0 top-full z-20 mt-2 max-h-64 overflow-auto rounded-3xl border-2 border-black bg-white py-2 shadow-[0_12px_32px_rgba(0,0,0,0.12)]
             invisible -translate-y-1 opacity-0 transition-[opacity,transform,visibility] duration-200 ease-out
             group-data-[open]:visible group-data-[open]:translate-y-0 group-data-[open]:opacity-100">
    @foreach ($options as $opt)
      <li role="option"
          data-select-option
          data-value="{{ $opt['value'] }}"
          aria-selected="{{ $opt['value'] === $selected['value'] ? 'true' : 'false' }}"
          class="group/opt flex cursor-pointer items-center justify-between gap-3 px-6 py-3 text-body-sm transition-colors
                 hover:bg-cream data-[active]:bg-cream aria-selected:text-red active:bg-red/10">
        <span>{{ $opt['label'] }}</span>
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"
             class="size-4 shrink-0 opacity-0 transition-opacity group-aria-selected/opt:opacity-100">
          <path d="m5 10 3.5 3.5L15 6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </li>
    @endforeach
  </ul>
</div>
