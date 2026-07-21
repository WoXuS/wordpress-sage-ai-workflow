<section class="c-hero">
  <x-container class="fl-py-12/24 flex flex-col fl-gap-12/24">
    <div class="flex flex-col fl-gap-6/12 md:flex-row md:items-center md:justify-between">
      <h1 class="text-red">{{ $hero['title'] }}</h1>
      <x-hexagon variant="solid" class="fl-w-60/120">
        <x-dynamic-icon :icon="$hero['icon']" class="fl-size-30/60!" />
      </x-hexagon>
    </div>
    @if ($hero['lead'])
      <p class="text-h2 text-center">{{ $hero['lead'] }}</p>
    @endif

  </x-container>
</section>
