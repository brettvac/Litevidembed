<?php
/**
 * @package    Litevidembed
 * @version    1.0
 * @license    GNU General Public License version 2
 */
namespace Naftee\Plugin\Content\Litevidembed\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;

class Litevidembed extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'replaceVideoShortcodes',
        ];
    }

   public function replaceVideoShortcodes(Event $event)
   {
    if (!$this->getApplication()->isClient('site'))
    {
        return; // Exit if this request is from the backend (administrator)
    }

    // Extract event arguments into variables
    [$context, $article, $params, $page] = array_values($event->getArguments());

    // Skip if the context is the indexer
    if ($context === 'com_finder.indexer')
    {
        return;
    }

    // Initialize text to process
    $text = null;

    // Handle different contexts and content properties
    if ($context === 'com_modules.module')
    {
        $text = $params->get('content', '');
    }
    elseif (isset($article->text))
    {
        $text = &$article->text;
    }
    elseif (isset($article->introtext))
    {
        $text = &$article->introtext;
    }
    elseif (isset($article->description))
    {
        $text = &$article->description;
    }
    else
    {
        // Try to process any string content in $article
        foreach ((array)$article as $key => $value)
        {
            if (is_string($value) && !empty($value) && strpos($value, '{youtube}') !== false || strpos($value, '{vimeo}') !== false)
            {
                $text = &$article->$key;
                break;
            }
        }
    }

    // Exit if no valid text found
    if ($text === null || empty($text))
    {
        return;
    }

    // Check if YouTube or Vimeo shortcodes exist in the text; exit early if neither is found
    if (strpos($text, '{youtube}') === false && strpos($text, '{vimeo}') === false)
    {
        return;
    }

    $loadYoutube = false;
    $loadVimeo = false;
    $offset = 0;

    // Loop through the text to find the next opening brace, starting from the current offset
    while (($start = strpos($text, '{', $offset)) !== false)
    {
        $platform = null;
        if (substr($text, $start, 9) === '{youtube}')
        {
            $platform = 'youtube';
            $tagLength = 9;
            $closingTag = '{/youtube}';
        }
        elseif (substr($text, $start, 7) === '{vimeo}')
        {
            $platform = 'vimeo';
            $tagLength = 7;
            $closingTag = '{/vimeo}';
        }

        if ($platform)
        {
            if (($end = strpos($text, $closingTag, $start)) !== false)
            {
                $tagContent = substr($text, $start + $tagLength, $end - $start - $tagLength);

                // Initialize variables
                $width = null;
                $videoUrl = $tagContent;

                // Check for width in shortcode
                if (strpos($tagContent, '|') !== false)
                {
                    $parts = explode('|', $tagContent);
                    $videoUrl = htmlspecialchars_decode($parts[0], ENT_QUOTES); // Decode URL or ID
                    if (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] <= 720)
                    {
                        $width = $parts[1] . 'px'; // Set width if valid and ≤ 720px
                    }
                }

                // Handle platform-specific logic
                $videoId = null;
                $replacement = '';

                switch ($platform)
                {
                    case 'youtube':
                        $videoId = $this->extractYoutubeId($videoUrl);
                        if ($videoId)
                        {
                            $divStyle = $width ? " style=\"width: {$width};\"" : '';
                            $replacement = "<div{$divStyle}><lite-youtube videoid=\"{$videoId}\"></lite-youtube></div>";
                            $loadYoutube = true;
                        }
                        break;

                    case 'vimeo':
                        $videoId = $this->extractVimeoId($videoUrl);
                        if ($videoId)
                        {
                            $divStyle = $width ? " style=\"width: {$width};\"" : '';
                            $replacement = "<div{$divStyle}><lite-vimeo videoid=\"{$videoId}\"></lite-vimeo></div>";
                            $loadVimeo = true;
                        }
                        break;

                    default:
                        // No valid platform, skip replacement
                        $offset = $end + strlen($closingTag);
                        continue 2; // Skip to next iteration of while loop
                }

                if ($videoId && $replacement)
                {
                    $text = substr_replace($text, $replacement, $start, $end - $start + strlen($closingTag));
                    $offset = $start + strlen($replacement);
                }
                else
                {
                    $offset = $end + strlen($closingTag);
                }
            }
            else
            {
                $offset = $start + 1;
            }
        }
        else
        {
            $offset = $start + 1;
        }
    }

    // Load assets only if needed
    if ($loadYoutube || $loadVimeo)
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        try
        {
            $wa->getRegistry()->addExtensionRegistryFile('plg_content_litevidembed');

            if ($loadYoutube)
            {
                $wa->useStyle('lite-youtube')
                   ->useScript('lite-youtube');
            }

            if ($loadVimeo)
            {
                $wa->useStyle('lite-vimeo')
                   ->useScript('lite-vimeo');
            }
        }
        catch (\Exception $e)
        {
            $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
            return;
        }
    }

    // Update module content if applicable
    if ($context === 'com_modules.module')
    {
        $params->set('content', $text);
    }
  }

    /**
     * Extracts the YouTube video ID from a given URL or raw ID string.
     *
     * Supports multiple YouTube URL formats, including:
     * - https://www.youtube.com/watch?v=VIDEO_ID
     * - https://youtu.be/VIDEO_ID
     * - https://www.youtube.com/shorts/VIDEO_ID
     * - video ID without URL
     *
     * @param   string  $url  The YouTube URL or direct video ID.
     * @return  string|false  Returns the video ID if matched, or false on failure.
     */
    protected function extractYoutubeId($url)
    {
        $patterns = [
            '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
            '/youtu\.be\/([^\&\?\/]+)/',
            '/youtube\.com\/shorts\/([^\&\?\/]+)/',
            '/^([a-zA-Z0-9_-]{11})$/' // Just the video ID without URL
        ];

        foreach ($patterns as $pattern)
        {
            if (preg_match($pattern, $url, $matches))
            {
                return $matches[1];
            }
        }
        return false;
    }

    /**
     * Extracts the Vimeo video ID from a given URL or raw ID string.
     *
     * Supports common Vimeo URL formats, including:
     * - https://vimeo.com/VIDEO_ID
     * - https://player.vimeo.com/video/VIDEO_ID
     * - Direct numeric video ID
     *
     * @param   string  $url  The Vimeo URL or direct video ID.
     * @return  string|false  Returns the video ID if matched, or false on failure.
     */
    protected function extractVimeoId($url)
    {
        $patterns = [
            '/vimeo\.com\/([0-9]+)/',
            '/player\.vimeo\.com\/video\/([0-9]+)/',
            '/^([0-9]+)$/' // Video ID without URL
        ];

        foreach ($patterns as $pattern)
        {
            if (preg_match($pattern, $url, $matches))
            {
                return $matches[1];
            }
        }
        return false;
    }
}
