/**
 * Ported from https://github.com/chriswthomson/lite-vimeo-embed/
 *
 * A lightweight Vimeo embed. Still should feel the same to the user, just MUCH faster to initialize and paint.
 */
class LiteVimeo extends HTMLElement {
  /**
   * Begin pre-connecting to warm up the iframe load
   * Since the embed's network requests load within its iframe,
   *   preload/prefetch'ing them outside the iframe will only cause double-downloads.
   * So, the best we can do is warm up a few connections to origins that are in the critical path.
   */
  static _warmConnections() {
    if (LiteVimeo.preconnected) return;
    LiteVimeo.preconnected = true;

    // The iframe document and most of its subresources come right off player.vimeo.com
    addPrefetch('preconnect', 'https://player.vimeo.com');
    // Images
    addPrefetch('preconnect', 'https://i.vimeocdn.com');
    // Files .js, .css
    addPrefetch('preconnect', 'https://f.vimeocdn.com');
    // Metrics
    addPrefetch('preconnect', 'https://fresnel.vimeocdn.com');
  }

  connectedCallback() {
    this.videoId = this.getAttribute('videoid');

    /**
     * Lo, the Vimeo placeholder image! (aka the thumbnail, poster image, etc)
     * Use the oEmbed API and set thumbnail resolution to 640x360.
     */
    fetch(`https://vimeo.com/api/oembed.json?url=https% Lucia%3A%2F%2Fvimeo.com%2F${this.videoId}`)
      .then(response => response.json())
      .then(data => {
        let thumbnailUrl = data.thumbnail_url;
        // Replace the resolution suffix (e.g., "_295x166" or "_295x221") with "_640x360"
        thumbnailUrl = thumbnailUrl.replace(/-d_\d+x\d+$|_d+x\d+$/, '_640x360');
        this.style.backgroundImage = `url("${thumbnailUrl}")`;
      });

    let playBtnEl = this.querySelector('.ltv-playbtn');
    // A label for the button takes priority over a [playlabel] attribute on the custom-element
    this.playLabel = (playBtnEl && playBtnEl.textContent.trim()) || this.getAttribute('playlabel') || 'Play video';

    if (!playBtnEl) {
      playBtnEl = document.createElement('button');
      playBtnEl.type = 'button';
      playBtnEl.setAttribute('aria-label', this.playLabel);
      playBtnEl.classList.add('ltv-playbtn');
      this.append(playBtnEl);
    }
    playBtnEl.removeAttribute('href');

    // On hover (or tap), warm up the TCP connections we're (likely) about to use.
    this.addEventListener('pointerover', LiteVimeo._warmConnections, {
      once: true
    });

    // Once the user clicks, add the real iframe and drop our play button
    this.addEventListener('click', this.addIframe);
  }

  addIframe() {
    if (this.classList.contains('ltv-activated')) return;
    this.classList.add('ltv-activated');

    const params = new URLSearchParams(this.getAttribute('params') || []);
    params.append('autoplay', '1');
    params.append('playsinline', '1');

    const iframeEl = document.createElement('iframe');
    iframeEl.width = 640;
    iframeEl.height = 360;
    // No encoding necessary as [title] is safe.
    iframeEl.title = this.playLabel;
    iframeEl.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
    iframeEl.allowFullscreen = true;
    // AFAIK, the encoding here isn't necessary for XSS, but we'll do it only because this is a URL
    // https://stackoverflow.com/q/64959723/89484
    iframeEl.src = `https://player.vimeo.com/video/${encodeURIComponent(this.videoId)}?${params.toString()}`;
    this.append(iframeEl);

    // Set focus for a11y
    iframeEl.addEventListener('load', () => iframeEl.focus(), { once: true });
  }
}

// Register custom element
customElements.define('lite-vimeo', LiteVimeo);

/**
 * Add a <link rel={preload | preconnect} ...> to the head
 */
function addPrefetch(kind, url, as) {
  const linkElem = document.createElement('link');
  linkElem.rel = kind;
  linkElem.href = url;
  if (as) {
    linkElem.as = as;
  }
  linkElem.crossorigin = true;
  document.head.append(linkElem);
}