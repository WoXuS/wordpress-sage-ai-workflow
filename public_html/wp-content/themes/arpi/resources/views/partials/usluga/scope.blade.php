@if ($scope)
  <section>
    <x-container data-reveal-group class="flex flex-col fl-gap-8/16 fl-pb-12/25">
      @if (!empty($scope_intro))
        <h2 data-reveal class="text-subtitle text-center text-red">{{ $scope_intro }}</h2>
      @endif
      @php $singleRow = count($scope) <= 5; @endphp
      <ul @class([
        'flex flex-wrap fl-gap-x-6/12 fl-gap-y-10/20 justify-center fl-px-5/10',
        'lg:flex-nowrap' => $singleRow,
      ])>
        @foreach ($scope as $item)
          <li data-reveal @class([
            'flex flex-col items-center text-center fl-gap-2/4',
            'lg:flex-1 lg:basis-0' => $singleRow,
            'lg:grow lg:shrink-0 lg:basis-[calc(25%-2.25rem)]' => !$singleRow,
          ])>
            <x-dynamic-icon :icon="$item['icon']" class="fl-size-10/16! text-red" />
            <p class="text-body-lg">{{ $item['label'] }}</p>
          </li>
        @endforeach
      </ul>
    </x-container>
  </section>
@endif
