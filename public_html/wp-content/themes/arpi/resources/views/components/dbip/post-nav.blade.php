@props(['nav'])
@if ($nav['prev'] || $nav['next'])
  <footer class="mt-4 flex flex-wrap items-start justify-between gap-4 border-t border-black/10 pt-8">
    <div class="min-w-0">
      @if ($nav['prev'])
        <x-dbip.nav-link :link="$nav['prev']" dir="prev" />
      @endif
    </div>
    <div class="min-w-0">
      @if ($nav['next'])
        <x-dbip.nav-link :link="$nav['next']" dir="next" />
      @endif
    </div>
  </footer>
@endif
