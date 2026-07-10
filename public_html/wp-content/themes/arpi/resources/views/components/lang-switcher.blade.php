@php
  $langs = function_exists('pll_the_languages')
    ? pll_the_languages(['raw' => 1, 'hide_if_empty' => 0])
    : null;
@endphp
<div class="flex items-center gap-1.5 text-body-sm uppercase tracking-wide max-lg:bg-white/40 max-lg:p-3 max-lg:w-fit max-lg:mx-auto max-lg:rounded-2xl max-lg:mt-4">
  @if ($langs)
    @foreach ($langs as $lang)
      @unless ($loop->first)<span class="text-body text-black/40">/</span>@endunless
      @if ($lang['current_lang'])
        <span class="text-red text-body max-lg:text-white" aria-current="true">{{ strtoupper($lang['slug']) }}</span>
      @else
        <a href="{{ $lang['url'] }}" class="c-btn c-btn--ghost text-black/40 hover:text-red/70 max-lg:text-black/60">{{ strtoupper($lang['slug']) }}</a>
      @endif
    @endforeach
  @else
    <span class="text-red text-body max-lg:text-white" aria-current="true">PL</span>
    <span class="text-body text-black/40">/</span>
    <a href="#" class="c-btn c-btn--ghost text-black/40 hover:text-red/70">EN</a>
  @endif
</div>
