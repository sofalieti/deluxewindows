<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
>
  <head>
    <meta charset="utf-8" />
    <title>{{ $seoTitle }} | Deluxe Windows Blog</title>
    <meta content="{{ $seoDescription }}" name="description" />
    <meta content="{{ $ogTitle }}" property="og:title" />
    <meta content="{{ $ogDescription }}" property="og:description" />
    @if($heroImage)
    <meta content="{{ $heroImage }}" property="og:image" />
    @endif
    <meta content="{{ $ogTitle }}" name="twitter:title" />
    <meta content="{{ $ogDescription }}" name="twitter:description" />
    @if($heroImage)
    <meta content="{{ $heroImage }}" name="twitter:image" />
    @endif
    <meta property="og:type" content="article" />
    <meta content="summary_large_image" name="twitter:card" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <link href="/webflow-assets/css/webflow.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/webflow-assets/css/fonts.css" media="all" />
    <script type="text/javascript">
      document.documentElement.className = document.documentElement.className
        .replace(/\bwf-loading\b/g, 'wf-active')
        .replace(/\bwf-exo-[^\s]+/g, '');
    </script>
    <script type="text/javascript">
      !(function (o, c) {
        var n = c.documentElement,
          t = " w-mod-";
        n.className += t + "js";
        ("ontouchstart" in o || (o.DocumentTouch && c instanceof DocumentTouch)) &&
          (n.className += t + "touch");
      })(window, document);
    </script>
    <link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    <style>
      .w-webflow-badge { display: none !important; }
      .blog-post-section { padding: 60px 20px 80px; }
      .blog-post-container { max-width: 800px; margin: 0 auto; }
      .blog-breadcrumb { font-size: 14px; color: #666; margin-bottom: 32px; }
      .blog-breadcrumb a { color: #1E73B9; text-decoration: none; }
      .blog-breadcrumb a:hover { text-decoration: underline; }
      .blog-breadcrumb span { margin: 0 6px; color: #aaa; }
      .blog-post-h1 {
        font-size: 2.4rem;
        font-weight: 800;
        line-height: 1.2;
        color: #1a1a1a;
        margin-bottom: 32px;
      }
      .blog-hero-image {
        width: 100%;
        border-radius: 12px;
        margin-bottom: 36px;
        display: block;
      }
      .blog-post-body p,
      .blog-post-body li {
        font-size: 1.05rem;
        line-height: 1.8;
        color: #333;
      }
      .blog-post-body p { margin-bottom: 20px; }
      .blog-post-body h2 {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 48px;
        margin-bottom: 16px;
      }
      .blog-post-body h3,
      .blog-post-body h4 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1E73B9;
        margin-top: 32px;
        margin-bottom: 12px;
      }
      .blog-post-body h5 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 24px;
        margin-bottom: 10px;
      }
      .blog-post-body ol,
      .blog-post-body ul { padding-left: 24px; margin-bottom: 20px; }
      .blog-post-body li { margin-bottom: 8px; }
      .blog-post-body img {
        width: 100%;
        border-radius: 10px;
        margin: 32px 0;
        display: block;
      }
      .blog-related-section { padding: 60px 20px 80px; background: #f7f9fc; }
      .blog-related-container { max-width: 1100px; margin: 0 auto; }
      .blog-related-h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 36px;
        text-align: center;
      }
      .blog-related-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
      }
      .blog-related-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s;
        display: flex;
        flex-direction: column;
      }
      .blog-related-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.13); }
      .blog-related-card img {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
      }
      .blog-related-card-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .blog-related-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.4;
      }
      @media (max-width: 768px) {
        .blog-post-h1 { font-size: 1.7rem; }
        .blog-related-grid { grid-template-columns: 1fr; }
      }
      @media (max-width: 480px) {
        .blog-post-h1 { font-size: 1.4rem; }
        .blog-post-body h2 { font-size: 1.35rem; }
      }
    </style>

    <script>
      (function () {
        let gtagLoaded = false;
        function loadGtag() {
          if (gtagLoaded) return;
          gtagLoaded = true;
          const script = document.createElement("script");
          script.src = "https://www.googletagmanager.com/gtag/js?id=G-JHYBB0THJM";
          script.async = true;
          document.head.appendChild(script);
          window.dataLayer = window.dataLayer || [];
          function gtag() { dataLayer.push(arguments); }
          window.gtag = gtag;
          gtag("js", new Date());
          gtag("config", "G-JHYBB0THJM");
        }
        document.addEventListener("DOMContentLoaded", loadGtag);
        document.addEventListener("mousemove", loadGtag, { once: true });
        document.addEventListener("touchstart", loadGtag, { once: true });
        document.addEventListener("scroll", loadGtag, { once: true });
      })();
    </script>
  </head>

  <body class="body">
    @include('partials.navbar')
    @include('partials.trust-badges')

    <section class="blog-post-section">
      <div class="blog-post-container">
        <nav class="blog-breadcrumb" aria-label="breadcrumb">
          <a href="/">Home</a>
          <span>/</span>
          <a href="/blog">Blog</a>
          <span>/</span>
          {{ $title }}
        </nav>

        <h1 class="blog-post-h1">{{ $title }}</h1>

        @if($heroImage)
        <img
          class="blog-hero-image"
          src="{{ $heroImage }}"
          alt="{{ $title }}"
          loading="eager"
        />
        @endif

        @if($bodyHtml)
        <div class="blog-post-body">
          {!! $bodyHtml !!}
        </div>
        @endif
      </div>
    </section>

    @if($relatedPosts->count() > 0)
    <section class="blog-related-section">
      <div class="blog-related-container">
        <h2 class="blog-related-h2">Read More Articles</h2>
        <div class="blog-related-grid">
          @foreach($relatedPosts as $post)
          <a href="/blog/{{ $post['slug'] }}" class="blog-related-card">
            @if($post['image'])
            <img src="{{ $post['image'] }}" alt="{{ $post['name'] }}" loading="lazy" />
            @endif
            <div class="blog-related-card-body">
              <div class="blog-related-card-title">{{ $post['name'] }}</div>
            </div>
          </a>
          @endforeach
        </div>
      </div>
    </section>
    @endif

    @include('partials.footer')
  </body>
</html>
