@if ($others)
  <section>
    <x-container class="flex flex-col fl-gap-8/12 fl-pb-12/25">
      <h2 class="text-subtitle uppercase text-red">{{ $labels['others_heading'] }}</h2>
      <ul class="flex flex-wrap justify-center fl-gap-x-8/16 fl-gap-y-10/16">
        @foreach ($others as $service)
          <li class="w-full max-w-xs xs:w-[calc(50%-2rem)] lg:w-[calc(33.333%-3rem)]">
            <a href="{{ $service['url'] }}"
               class="group/card flex h-full flex-col items-center text-center fl-gap-3/5">
              <span class="grid place-items-center rounded-2xl bg-red text-white fl-size-32/52">
                <x-dynamic-icon :icon="$service['icon']" class="fl-size-14/22!" />
              </span>
              <h3 class="text-h3 uppercase text-red">{{ $service['name'] }}</h3>
              @if ($service['desc'])
                <p class="text-body-sm">{{ $service['desc'] }}</p>
              @endif
              <span class="c-btn c-btn--ghost mt-auto">
                {{ $labels['more'] }}
                @svg('icon-arrow-right', 'size-5')
              </span>
            </a>
          </li>
        @endforeach
      </ul>
    </x-container>
  </section>
@endif
