@props(['variant' => 'solid'])
<div {{ $attributes->merge(['class' => "c-hex c-hex--{$variant}"]) }} @if($variant === 'outline') aria-hidden="true" @endif>
  <div class="c-hex__inner">{{ $slot }}</div>
</div>
