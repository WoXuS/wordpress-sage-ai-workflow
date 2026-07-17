@props(['post'])
@php $id = $post->ID; @endphp
<a href="{{ get_permalink($id) }}" class="group/card flex h-full flex-col gap-5">
  <div class="aspect-412/400 w-full shrink-0 overflow-hidden rounded-[30px] bg-red">
    @if (has_post_thumbnail($id))
      {!! get_the_post_thumbnail($id, 'large', [
        'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover/card:scale-105',
        'loading' => 'lazy',
      ]) !!}
    @endif
  </div>
  <div class="flex flex-1 flex-col gap-3">
    <h3 class="max-md:text-h2 transition-colors group-hover/card:text-red">{{ get_the_title($id) }}</h3>
    <div data-clamp-fill class="text-body-sm hidden md:block md:grow md:shrink-0 md:basis-[2lh] md:overflow-hidden">
      <p>{{ get_the_excerpt($id) }}</p>
    </div>
    <span class="c-btn c-btn--ghost mt-auto self-start md:mt-0">Więcej @svg('icon-arrow-right', 'size-5')</span>
  </div>
</a>
