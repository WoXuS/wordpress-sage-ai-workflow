@php
  $langs = function_exists('pll_the_languages')
    ? pll_the_languages(['raw' => 1, 'hide_if_empty' => 0])
    : null;
@endphp
<div class="flex items-center gap-1.5 text-body-sm uppercase tracking-wide">
  @if ($langs)
    @foreach ($langs as $i => $lang)
      @if ($i > 0)<span class="text-black/40">/</span>@endif
      <a href="{{ $lang['url'] }}"
         @class(['text-red' => $lang['current_lang'], 'text-black/40 hover:text-red' => ! $lang['current_lang']])
         @if ($lang['current_lang']) aria-current="true" @endif>{{ strtoupper($lang['slug']) }}</a>
    @endforeach
  @else
    <span class="text-red" aria-current="true">PL</span>
    <span class="text-black/40">/</span>
    <a href="#" class="text-black/40 hover:text-red">EN</a>
  @endif
</div>
