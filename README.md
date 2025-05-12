# Lite Vid Embed - Joomla! Content Plugin
 ![Lite Vid Embed logo](lite-vid-embed.jpg)

Lite Video Embed is a Joomla! plugin that helps your website load faster by making video loading more efficient. 

Supported video sites:
- YouTube
- Vimeo

## Why Use Lite Video Embed?
Normally, embedded videos from platforms like YouTube and Vimeo use iframes, which are windows that load an entire external webpage inside your site. This can slow down loading times because it requires downloading extra scripts and data. 

Instead, Lite Video Embed replaces iframes with facades—static preview images that look like the video but don't load any heavy resources. The actual video only loads when the user clicks on it, improving speed, SEO, and user experience.

## Installation
1. Just download the plugin and install it using the extensions manager. Alternatively, you can install from Web [by using this direct link](https://github.com/brettvac/Litevidembed/releases/download/1.0/litevidembed.zip)
2. You will then need to Activate it in Extensions > Plugins.

## How To Use Lite Video Embed
Add a simple shortcode to your content. 

For YouTube, use the syntax `{youtube}VIDEO_ID{/youtube}`

For vimeo videos, simply use `{lite-vimeo}VIDEO_ID{/lite-vimeo}`

To set the embed width in pixels, use `|WIDTH`. The width must be numeric and less than 720px; otherwise, the default width (up to 720px, based on the container) is used.

Example usage to set width:
- `{youtube}https://www.youtube.com/watch?v=VIDEO_ID|300{/youtube}`

### Supported video IDs
Use any of the following video IDs in your shortcodes.

#### YouTube
- Standard watch URL: `{youtube}https://www.youtube.com/watch?v=abc123xyz{/youtube}`
- Shortened URL: `{youtube}https://youtu.be/abc123xyz?si=abc123{/youtube}`
- Shorts URL: `{youtube}https://www.youtube.com/shorts/abc123xyz{/youtube}`
- Video ID: `{youtube}abc123xyz{/youtube}`

#### Vimeo
- Standard URL: `{vimeo}https://vimeo.com/123456789{/vimeo}`
- Player URL: `{vimeo}https://player.vimeo.com/video/123456789{/vimeo}`
- Video ID: `{vimeo}123456789{/vimeo}`


## FAQ
**Q: What are the Joomla! and PHP requirements?**  
**A:** This plugin requires Joomla versions greater than 4.4 and PHP 8.1.

**Q: This plugin is awesome! Can I send a donation?**  
**A:** Sure! Send your cryptonation to the following wallets:

`BTC 1PXWZJcBfehqgV25zWdVDS6RF2yVMxFkZD`

`Eth 0xC9b695D4712645Ba178B4316154621B284e2783D`

**Q: Got any more awesome Joomla! plugins?**  
**A:** Find them [right here](https://naftee.com)

Contributing
------------
- **[Lite Youtube Embed](https://github.com/paulirish/lite-youtube-embed)** by Paul Irish
- **[Lite Vimeo Embed](https://github.com/chriswthomson/lite-vimeo-embed/)** by Chris Thomson
- **[Facades Plugin](https://brokenlinkchecker.dev/extensions/plg-system-facades)**
- **[Lite Youtube](https://github.com/brianteeman/ytlite)** by Brian Teeman
