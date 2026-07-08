@props(['variant' => 'solid', 'href' => null, 'type' => 'button'])
@php $tag = $href ? 'a' : 'button'; @endphp
<{{ $tag }}
  @if($href) href="{{ $href }}" @else type="{{ $type }}" @endif
  {{ $attributes->merge(['class' => "c-btn c-btn--{$variant}"]) }}>
  {{ $slot }}
</{{ $tag }}>
