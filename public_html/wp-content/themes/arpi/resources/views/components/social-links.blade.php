@props(['links' => [], 'variant' => 'light'])
@php
  $styles = $variant === 'dark'
    ? 'border-black/20 text-black hover:bg-red hover:border-red hover:text-white'
    : 'border-white/70 text-white hover:bg-white hover:text-red';
@endphp
<ul {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
  @foreach ($links as $link)
    <li>
      <a href="{{ $link['url'] }}" aria-label="{{ $link['network'] }}"
         class="flex size-10 items-center justify-center rounded-full border-2 transition-colors {{ $styles }}">
        @svg('icon-' . $link['icon'], 'size-4!')
      </a>
    </li>
  @endforeach
</ul>
