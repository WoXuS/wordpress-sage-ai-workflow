@props(['variant' => 'solid'])
{{-- Rounded-corner pointy-top hexagon. Shape is an SVG path (viewBox 90x80 = 9:8) so
     both fill (solid) and stroke (outline) get the brand's rounded vertices. --}}
<div {{ $attributes->merge(['class' => "c-hex c-hex--{$variant}"]) }} @if($variant === 'outline') aria-hidden="true" @endif>
  <svg class="c-hex__shape" viewBox="0 0 90 80" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path d="M39.52,2.44Q45,0 50.48,2.44L84.52,17.56Q90,20 90,26L90,54Q90,60 84.52,62.44L50.48,77.56Q45,80 39.52,77.56L5.48,62.44Q0,60 0,54L0,26Q0,20 5.48,17.56Z" />
  </svg>
  <div class="c-hex__inner">{{ $slot }}</div>
</div>
