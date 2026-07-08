@props(['links' => []])
<ul class="flex items-center gap-3">
  @foreach ($links as $link)
    <li>
      <a href="{{ $link['url'] }}" aria-label="{{ $link['network'] }}"
         class="flex size-10 items-center justify-center rounded-full border-2 border-white/70 text-white transition-colors hover:bg-white hover:text-red">
        @svg('icon-' . $link['icon'], 'size-4')
      </a>
    </li>
  @endforeach
</ul>
