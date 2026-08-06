<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Gate scroll-reveal before first paint (JS may load late); failsafe unhides if reveal.js never inits. --}}
    <script>
      (function () {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var el = document.documentElement;
        el.classList.add('reveal-ready');
        addEventListener('load', function () {
          setTimeout(function () {
            if (!window.__revealReady) el.classList.remove('reveal-ready');
          }, 1200);
        });
      })();
    </script>

    @php(do_action('get_header'))
    @php(wp_head())

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body @php(body_class())>
    @php(wp_body_open())

    <div id="app">
      <a class="sr-only focus:not-sr-only" href="#main">
        {{ __('Skip to content', 'sage') }}
      </a>

      @include('sections.header')

      <main id="main" class="main">
        @yield('content')
      </main>

      @hasSection('sidebar')
        <aside class="sidebar">
          @yield('sidebar')
        </aside>
      @endif

      @include('sections.footer')
    </div>

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
