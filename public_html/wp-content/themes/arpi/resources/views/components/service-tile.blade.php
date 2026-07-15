@props(['service'])

<a href="{{ $service['url'] }}"
   class="group/tile flex flex-col justify-between items-center fl-gap-8/20 rounded-2xl bg-red fl-px-5/10 fl-pb-7/10 fl-pt-20/40 text-white transition-shadow hover:shadow-lg">
  @svg('icon-' . $service['icon'], 'size-40!')
  <div class="flex items-end justify-between gap-7 w-full">
    <h2>{{ $service['name'] }}</h2>
    <span
      class="grid size-10 shrink-0 place-items-center rounded-full border border-white transition-transform group-hover/tile:translate-x-1">
      @svg('icon-arrow-right', 'size-5!')
    </span>
  </div>
</a>
