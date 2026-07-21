@props(['icon'])
@php $class = $attributes->get('class'); @endphp
@if (($icon['type'] ?? 'svg') === 'file' && ! empty($icon['url']))
  <img src="{{ $icon['url'] }}" alt="" aria-hidden="true" {{ $attributes->merge(['class' => 'inline-block object-contain']) }}>
@else
  @svg('icon-' . ($icon['name'] ?? 'three-papers'), $class)
@endif
