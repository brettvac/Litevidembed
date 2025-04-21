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
1. Just download the plugin and install it using the extensions manager. 
*Alternatively, you can install from Web [by using this direct link](https://github.com/brettvac/Lite-vid-embed/releases/download/1.0/litevidembed.zip)
2. You will then need to Activate it in Extensions > Plugins.

## How To Use Lite Video Embed
Add a simple shortcode to your content. 

For YouTube, use the syntax
`{youtube}VIDEO_ID{/youtube}`

For vimeo videos, simply use
`{lite-vimeo}VIDEO_ID{/lite-vimeo}`

### Supported video IDs
Use any of the following video IDs in your shortcodes.

#### YouTube
- Standard watch URL: `{youtube}https://www.youtube.com/watch?v=dQw4w9WgXcQ{/youtube}`
- Shortened URL: `{youtube}https://youtu.be/dQw4w9WgXcQ?si=abc123{/youtube}`
- Shorts URL: `{youtube}https://www.youtube.com/shorts/abc123xyz{/youtube}`

#### Vimeo
- Standard URL: `{vimeo}https://vimeo.com/123456789{/vimeo}`
- Player URL: `{vimeo}https://player.vimeo.com/video/123456789{/vimeo}`

## Requirements
This plugin requires Joomla versions greater than 4.4 and PHP 8.1.

Contributing
------------
**[Lite Youtube Embed](https://github.com/paulirish/lite-youtube-embed)** by Paul Irish
**[Lite Vimeo Embed](https://github.com/luwes/lite-vimeo-embed)** by Wesley Luyten
**[Facades Plugin](https://brokenlinkchecker.dev/extensions/plg-system-facades)**
**[Lite Youtube](https://github.com/brianteeman/ytlite)** by Brian Teeman