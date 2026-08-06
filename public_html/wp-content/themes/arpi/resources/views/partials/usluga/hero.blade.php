<section class="c-hero">
  <x-container class="fl-py-12/24 flex flex-col fl-gap-12/24">
    <div class="flex flex-col fl-gap-6/12 md:flex-row md:items-center md:justify-between">
      <h1 data-hero style="--hero-delay:100ms" class="text-red">{{ $hero['title'] }}</h1>
      <x-hexagon variant="solid" data-hero="image" style="--hero-delay:220ms" class="fl-w-60/120">
        <x-dynamic-icon :icon="$hero['icon']" class="fl-size-30/60!" />
      </x-hexagon>
    </div>
    @if ($hero['lead'])
      <p data-hero style="--hero-delay:340ms" class="text-h2 text-center">{{ $hero['lead'] }}</p>
    @endif

  </x-container>
</section>
