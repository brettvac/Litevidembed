class LiteVimeo extends (globalThis.HTMLElement ?? class {}) {
  /**
   * Begin pre-connecting to warm up the iframe load
   * Since the embed's network requests load within its iframe,
   *   preload/prefetch'ing them outside the iframe will only cause double-downloads.
   * So, the best we can do is warm up a few connections to origins that are in the critical path.
   */
  static _warmConnections() {
    if (LiteVimeo.preconnected) return;
    LiteVimeo.preconnected = true;
    addPrefetch('preconnect', 'https://player.vimeo.com');
    addPrefetch('preconnect', 'https://i.vimeocdn.com');
    addPrefetch('preconnect', 'https://f.vimeocdn.com');
    addPrefetch('preconnect', 'https://fresnel.vimeocdn.com');
  }

  connectedCallback() {
    this.videoId = this.getAttribute('videoid');

    // Fetch the thumbnail from Vimeo's oEmbed API and set it as the background
    fetch(`https://vimeo.com/api/oembed.json?url=https%3A%2F%2Fvimeo.com%2F${this.videoId}`)
      .then(response => response.json())
      .then(data => {
        let thumbnailUrl = data.thumbnail_url;
        // Replace the size portion with 1280x720 for highest quality thumbnail
        thumbnailUrl = thumbnailUrl.replace(/_\d+x\d+/, '_1280x720');
        this.style.backgroundImage = `url("${thumbnailUrl}")`;
      });
    
    // Set up play button, and its visually hidden label
    let playBtnEl = this.querySelector('.ltv-playbtn');
    this.playLabel = (playBtnEl && playBtnEl.textContent.trim()) || this.getAttribute('playlabel') || 'Play video';

    // If no play button exists, create one
    if (!playBtnEl) {
      playBtnEl = document.createElement('button');
      playBtnEl.type = 'button';
      playBtnEl.setAttribute('aria-label', this.playLabel);
      playBtnEl.classList.add('ltv-playbtn');
      this.append(playBtnEl);
    }
    playBtnEl.removeAttribute('href'); // Ensure no href attribute remains from potential misuse
    
    // On hover (or tap), warm up the TCP connections we're (likely) about to use
    this.addEventListener('pointerover', LiteVimeo._warmConnections, { once: true });
    // Add iframe on click
    this.addEventListener('click', this.addIframe);
  }

  /**
   * Add the iframe when the user clicks, replacing the placeholder
   */
  addIframe() {
    if (this.classList.contains('ltv-activated')) return;
    this.classList.add('ltv-activated');

    const iframeEl = document.createElement('iframe');
    iframeEl.width = 640;  // Default width, will be overridden by CSS
    iframeEl.height = 360; // Default height, will be overridden by CSS
    iframeEl.title = this.playLabel; // No encoding necessary as [title] is safe for XSS
    iframeEl.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
    // URL encoding for src to ensure safety, though not strictly necessary here
    iframeEl.src = `https://player.vimeo.com/video/${encodeURIComponent(this.videoId)}?autoplay=1`;
    this.append(iframeEl);

    // Set focus for accessibility after loading
    iframeEl.addEventListener('load', () => iframeEl.focus(), { once: true });
  }
}

/**
 * Register custom element only if not already defined
 */
if (globalThis.customElements && !globalThis.customElements.get('lite-vimeo')) {
  globalThis.customElements.define('lite-vimeo', LiteVimeo);
}

/**
 * Add a <link rel={preload | preconnect} ...> to the head
 * @param {string} kind - Type of prefetch (e.g., 'preconnect')
 * @param {string} url - URL to prefetch
 * @param {string} [as] - Optional 'as' attribute for prefetch
 */
function addPrefetch(kind, url, as) {
  const linkElem = document.createElement('link');
  linkElem.rel = kind;
  linkElem.href = url;
  if (as) linkElem.as = as;
  linkElem.crossorigin = true; // Required for cross-origin preconnects
  document.head.append(linkElem);
}