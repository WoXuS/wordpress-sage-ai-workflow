@props(['items'])
<nav {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-2 gap-y-1 text-body-sm']) }}
     aria-label="Breadcrumb">
  @foreach ($items as $i => $item)
    @if ($i > 0)
      @svg('icon-arrow-right', 'size-4 shrink-0 text-red/50')
    @endif
    @php $label = (!empty($item['prefix']) ? '<span class="text-red/60">'.e($item['prefix']).'</span> ' : '').e($item['text']); @endphp
    @if (!empty($item['url']))
      <a href="{{ $item['url'] }}" class="text-red hover:text-red-dark">{!! $label !!}</a>
    @else
      <span class="font-medium text-black">{!! $label !!}</span>
    @endif
  @endforeach
</nav>
