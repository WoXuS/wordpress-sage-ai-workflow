@props(['type' => 'text', 'variant' => null])
<input
  type="{{ $type }}"
  {{ $attributes->merge(['class' => 'c-input' . ($variant === 'on-red' ? ' c-input--on-red' : '')]) }}
/>
