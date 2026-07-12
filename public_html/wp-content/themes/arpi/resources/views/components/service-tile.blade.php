@props(['service'])

<a href="{{ $service['url'] }}"
   class="group/tile flex flex-col justify-between fl-gap-8/12 rounded-2xl bg-red p-6 text-white transition-shadow hover:shadow-lg lg:p-8">
  @svg('icon-' . $service['icon'], 'size-12!')
  <div class="flex items-end justify-between gap-4">
    <span class="text-h3">{{ $service['name'] }}</span>
    <span class="grid size-10 shrink-0 place-items-center rounded-full border border-white transition-transform group-hover/tile:translate-x-1">
      @svg('icon-arrow-right', 'size-5!')
    </span>
  </div>
</a>
