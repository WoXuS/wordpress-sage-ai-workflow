@props(['post'])
@php $id = $post->ID; @endphp
<a href="{{ get_permalink($id) }}" class="group/card flex flex-col gap-4">
  <div class="aspect-[412/400] w-full overflow-hidden rounded-2xl bg-red">
    @if (has_post_thumbnail($id))
      {!! get_the_post_thumbnail($id, 'large', [
        'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover/card:scale-105',
        'loading' => 'lazy',
      ]) !!}
    @endif
  </div>
  <h3 class="transition-colors group-hover/card:text-red">{{ get_the_title($id) }}</h3>
  <span class="c-btn c-btn--ghost self-start">Więcej @svg('icon-arrow-right', 'size-5')</span>
</a>
