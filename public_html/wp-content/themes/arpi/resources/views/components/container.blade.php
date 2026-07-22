@props(['as' => 'div', 'variant' => null])
@php
  $variantClass = match ($variant) {
      'header' => ' o-wrap--header',
      'wide' => ' o-wrap--wide',
      default => '',
  };
@endphp
<{{ $as }} {{ $attributes->merge(['class' => 'o-wrap'.$variantClass]) }}>
  {{ $slot }}
</{{ $as }}>
