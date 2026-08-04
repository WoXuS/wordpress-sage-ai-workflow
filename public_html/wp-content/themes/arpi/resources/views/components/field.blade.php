@props([
  'label' => null,
  'hint' => null,
  'required' => false,
  'as' => 'label', // 'label' for a single labelable control; 'div' for composite fields (e.g. Trix)
])

@php $tag = $as === 'div' ? 'div' : 'label'; @endphp

<{{ $tag }} {{ $attributes->class('flex flex-col gap-2') }}>
  @if ($label)
    <span @class(['text-body-sm', "after:content-['*'] after:ml-1 after:text-error" => $required])>{{ $label }}</span>
  @endif

  {{ $slot }}

  @if ($hint)
    <span class="text-body-sm opacity-70">{{ $hint }}</span>
  @endif
</{{ $tag }}>
