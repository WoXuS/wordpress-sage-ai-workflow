@if ($scope)
  <section>
    <x-container data-reveal-group class="flex flex-col fl-gap-8/16 fl-pb-12/25">
      @if (!empty($scope_intro))
        <h2 data-reveal class="text-subtitle text-center text-red">{{ $scope_intro }}</h2>
      @endif
      {{-- Base columns are 2/3/4 (mobile/md/lg). A tile whose label would wrap past two
           lines is widened to its own column by scope-fit.js so it stays ~2 lines instead
           of stacking 3-4 — rows may end up mixed (2, then a wide 1, then 2). flex-wrap +
           justify-center centers each row. --}}
      <ul data-scope-list class="flex flex-wrap justify-center items-start fl-gap-x-6/12 fl-gap-y-10/20 fl-px-5/10">
        @foreach ($scope as $item)
          <li data-scope-tile data-reveal class="flex flex-col items-center text-center fl-gap-2/4 basis-[calc(50%_-_1.5rem)] md:basis-[calc(33.333%_-_2rem)] lg:basis-[calc(25%_-_2.25rem)]">
            <x-dynamic-icon :icon="$item['icon']" class="fl-size-10/16! text-red" />
            <p class="text-body-lg">{{ $item['label'] }}</p>
          </li>
        @endforeach
      </ul>
    </x-container>
  </section>
@endif
