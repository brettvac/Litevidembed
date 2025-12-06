# Lite Vid Embed - Joomla! Content Plugin
![Lite Vid Embed logo](lite-vid-embed.jpg)

Lite Video Embed is a Joomla! plugin that helps your website load faster by making video loading from YouTube and Vimeo more efficient. 

## Why Use Lite Video Embed?
Normally, embedded videos from platforms like YouTube and Vimeo use iframes, which are windows that load an entire external webpage inside your site. If you simply insert the iframe directly in your content, this approach slow down loading times because it requires downloading extra scripts and data. 

Instead, the Lite Video Embed content plugin first displays facades—static preview images that look like the video but don't load any heavy resources. The actual iframe for the video only loads when the user clicks on the facade image. This will improve speed, SEO, and user experience.

## Installation
1. Go to the extensions manager and install from Web by using this direct link: [https://github.com/brettvac/Litevidembed/releases/latest/download/plg_content_litevidembed.zip](https://github.com/brettvac/Litevidembed/releases/latest/download/plg_content_litevidembed.zip)
2. You will then need to Activate it in Extensions > Plugins.

## How To Use Lite Video Embed
Add a simple shortcode to your content to embed a YouTube or Vimeo video. 

- For YouTube, use the syntax `{youtube}VIDEO_ID{/youtube}`
- For Vimeo videos, simply use `{lite-vimeo}VIDEO_ID{/lite-vimeo}`

To set the embed width in pixels, use `|WIDTH`. The width must be numeric and less than 720px; otherwise, the default width (up to 720px, based on the container) is used.
Example usage to set width:
- `{youtube}https://www.youtube.com/watch?v=VIDEO_ID|300{/youtube}`

## Supported video sites:
- YouTube
- Vimeo

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

## Requirements
This plugin requires Joomla versions greater than 4.4 and PHP 7.2.5.

## FAQ
**Q: What are the Joomla! and PHP requirements?**  
**A:** This plugin requires Joomla versions 4.4 and up and PHP 7.2.5 and up.

**Q: Will this plugin load Dailymotion or Rumble videos?**  
**A:** No, this plugin is set up to work with Vimeo and YouTube only at present.

**Q: This plugin is awesome! Can I send a donation?**  
**A:** Sure! Send your cryptonation to the following wallets:

`BTC 1PXWZJcBfehqgV25zWdVDS6RF2yVMxFkZD`

`Eth 0xC9b695D4712645Ba178B4316154621B284e2783D`

**Q: Got any more awesome Joomla! plugins?**  
**A:** Find them [right here](https://naftee.com)

Contributing
------------
- **Lite Youtube Embed** by Paul Irish: [https://github.com/paulirish/lite-youtube-embed](https://github.com/paulirish/lite-youtube-embed)
- **Lite Vimeo Embed** by Chris Thomson: [https://github.com/chriswthomson/lite-vimeo-embed/](https://github.com/chriswthomson/lite-vimeo-embed/)
- **Facades Plugin**: [https://brokenlinkchecker.dev/extensions/plg-system-facades](https://brokenlinkchecker.dev/extensions/plg-system-facades)
- **Lite Youtube** by Brian Teeman: [https://github.com/brianteeman/ytlite](https://github.com/brianteeman/ytlite)
