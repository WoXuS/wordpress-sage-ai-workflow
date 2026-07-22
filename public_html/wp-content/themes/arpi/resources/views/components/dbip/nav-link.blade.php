@props(['link', 'dir'])
<x-button variant="outline" :href="$link['url']"
          class="group max-w-full !justify-start !whitespace-normal !text-left">
  @if ($dir === 'prev')
    @svg('icon-arrow-right', 'size-4 shrink-0 rotate-180 transition-transform group-hover:-translate-x-1')
  @endif
  <span class="min-w-0">
    @if ($link['is_chapter'])
      <span class="block text-body-sm text-red/60 group-hover:text-white/75">{{ $link['prefix'] }}</span>
      <span class="block truncate font-medium">{{ $link['title'] }}</span>
    @else
      <span class="block truncate"><span class="text-red/60 group-hover:text-white/75">{{ $link['prefix'] }}</span> {{ $link['title'] }}</span>
    @endif
  </span>
  @if ($dir === 'next')
    @svg('icon-arrow-right', 'size-4 shrink-0 transition-transform group-hover:translate-x-1')
  @endif
</x-button>
