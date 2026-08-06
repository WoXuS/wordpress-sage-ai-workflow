@props(['service'])

<a href="{{ $service['url'] }}"
   {{ $attributes->merge(['class' => 'group/tile flex flex-col justify-between items-center fl-gap-8/20 rounded-2xl bg-red fl-px-4/10 fl-pb-7/10 pt-10 xs:fl-pt-15/38 text-white transition-shadow hover:shadow-lg']) }}>
  @svg('icon-' . $service['icon'], 'fl-size-20/40!')
  <div class="flex items-end justify-between fl-gap-2/7 w-full">
    <h2 class="text-h3 lg:max-xl:text-subtitle xl:text-h2">{{ $service['name'] }}</h2>
    <div
      class="grid fl-size-5.5/10 shrink-0 place-items-center rounded-full border border-white transition-transform group-hover/tile:translate-x-1">
      @svg('icon-arrow-right', 'fl-size-4/5!')
    </div>
  </div>
</a>
