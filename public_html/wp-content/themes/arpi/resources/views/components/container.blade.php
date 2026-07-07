@props(['as' => 'div', 'variant' => null])
<{{ $as }} {{ $attributes->merge(['class' => 'o-wrap' . ($variant === 'header' ? ' o-wrap--header' : '')]) }}>
  {{ $slot }}
</{{ $as }}>
