@props(['variant' => 'solid'])
{{-- Rounded-corner pointy-top hexagon. Shape is an SVG path (viewBox 90x80 = 9:8) so
     both fill (solid) and stroke (outline) get the brand's rounded vertices. --}}
<div {{ $attributes->merge(['class' => "c-hex c-hex--{$variant}"]) }} @if($variant === 'outline') aria-hidden="true" @endif>
  <svg class="c-hex__shape" viewBox="0 0 90 80" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path d="M34.95,4.47Q45,0 55.05,4.47L79.95,15.53Q90,20 90,31L90,49Q90,60 79.95,64.47L55.05,75.53Q45,80 34.95,75.53L10.05,64.47Q0,60 0,49L0,31Q0,20 10.05,15.53Z" />
  </svg>
  <div class="c-hex__inner">{{ $slot }}</div>
</div>
