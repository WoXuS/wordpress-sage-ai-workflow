@props(['variant' => 'solid', 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
{{-- Flat-top hexagon using the exact Figma shape (resources/images/purple-hexagon.svg,
     viewBox 417x372). solid = filled (white text over red); outline = stroked, fills
     red on hover. Pass :href to make it a link, otherwise it's a plain <div>. --}}
<{{ $tag }}
  @if($href) href="{{ $href }}" @endif
  {{ $attributes->merge(['class' => "c-hex c-hex--{$variant}"]) }}
  @if($variant === 'outline' && ! $href) aria-hidden="true" @endif>
  <svg class="c-hex__shape" viewBox="0 0 417 372" preserveAspectRatio="none" aria-hidden="true" focusable="false">
    <path d="M7.52344 204.513C0.82476 192.91 0.82476 178.615 7.52344 167.013L91.6797 21.25C98.3784 9.64767 110.758 2.50009 124.155 2.5H292.468C305.865 2.5001 318.245 9.64768 324.943 21.25L409.1 167.013C415.694 178.434 415.796 192.464 409.408 203.967L409.1 204.513L324.943 350.275C318.245 361.878 305.865 369.025 292.468 369.025H124.155C110.758 369.025 98.3784 361.878 91.6797 350.275L7.52344 204.513Z" />
  </svg>
  <div class="c-hex__inner">{{ $slot }}</div>
</{{ $tag }}>
