@props(['post'])
@php $id = $post->ID; @endphp
{{-- Whole card links to the post: branded ARPI thumbnail (red fallback block) + title + excerpt. --}}
<a href="{{ get_permalink($id) }}" class="group/card flex flex-col fl-gap-4/6">
  <div class="aspect-[412/400] w-full overflow-hidden rounded-2xl bg-red">
    @if (has_post_thumbnail($id))
      {!! get_the_post_thumbnail($id, 'large', [
        'class' => 'h-full w-full object-cover transition-transform duration-300 group-hover/card:scale-105',
        'loading' => 'lazy',
      ]) !!}
    @endif
  </div>
  <div class="flex flex-col gap-2">
    <h3 class="transition-colors group-hover/card:text-red">{{ get_the_title($id) }}</h3>
    <p class="text-body-sm opacity-80">{{ get_the_excerpt($id) }}</p>
  </div>
</a>
