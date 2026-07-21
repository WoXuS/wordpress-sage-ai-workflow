@if (!empty($reports['items']))
  <section>
    <x-container class="fl-pb-12/20">
      <div class="flex flex-col fl-gap-5/8">
        <h2 class="text-subtitle text-red text-wrap">{{ $reports['heading'] }}</h2>
        <ul class="flex list-disc flex-col fl-gap-2/3 pl-6 text-body-lg marker:text-red">
          @foreach ($reports['items'] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>
      </div>
    </x-container>
  </section>
@endif
