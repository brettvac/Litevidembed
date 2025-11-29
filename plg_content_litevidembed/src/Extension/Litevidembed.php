<?php
/**
 * @package    Litevidembed
 * @version    1.1
 * @license    GNU General Public License version 2
 */
namespace Naftee\Plugin\Content\Litevidembed\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;

/**
 * Litevidembed plugin to embed lightweight YouTube and Vimeo videos using shortcodes.
 */
class Litevidembed extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepare' => 'replaceVideoShortcodes',
        ];
    }

    /**
     * Replaces YouTube and Vimeo shortcodes with lightweight video embeds.
     *
     * @param   Event  $event  The onContentPrepare event.
     * @return  void
     */
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
        if ($context === 'com_content.article' || $context === 'com_content.category' || $context === 'com_content.featured')
        {
            $text = $article->text; 
        }
        elseif ($context === 'com_modules.module')
        {
            $text = $params->get('content', '');
        }
        else
        {
           $text = $article->text;  // Fallback for other contexts
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

                    if (strpos($tagContent, '<') !== false)
                    {
                        // We assume the first opening tag was a mistake. 
                        // Move offset forward by 1 so we can discover the next tag in the next iteration.
                        $offset = $start + 1;
                        continue;
                    }

                    // Initialize variables
                    $width = null;
                    $videoUrl = $tagContent;

                    // Check for width of video in shortcode
                    if (strpos($tagContent, '|') !== false)
                    {
                        $parts = explode('|', $tagContent);
                        $videoUrl = htmlspecialchars_decode($parts[0], ENT_QUOTES); // Decode URL or ID
                        if (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] <= 720)
                        {
                            $width = $parts[1] . 'px'; // Set width if valid and ≤ 720px
                        }
                    }

                    // Create lite video element depending on the platform in the shortcode
                    $videoId = null;
                    $replacement = '';

                    switch ($platform)
                    {
                        case 'youtube':
                            $videoId = $this->extractYoutubeId($videoUrl);
                            if ($videoId)
                            {
                                $style = $width ? " style=\"width: {$width};\"" : "";
                                $replacement = "<lite-youtube videoid=\"{$videoId}\"{$style}></lite-youtube>";
                                $loadYoutube = true;
                            }
                            break;

                        case 'vimeo':
                            $videoId = $this->extractVimeoId($videoUrl);
                            if ($videoId)
                            {
                                $style = $width ? " style=\"width: {$width};\"" : "";
                                $replacement = "<lite-vimeo videoid=\"{$videoId}\"{$style}></lite-vimeo>";
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
            $document = Factory::getDocument();
            $wa = $document->getWebAssetManager();

            try
            {
                $wa->getRegistry()->addExtensionRegistryFile('plg_content_litevidembed');

                if ($loadYoutube)
                {
                    $wa->useStyle('plg_content_litevidembed.lite-youtube')
                       ->useScript('plg_content_litevidembed.lite-youtube');
                    $style = 'lite-youtube { margin: 0 auto; }';
                    $document->addStyleDeclaration($style);
                }

                if ($loadVimeo)
                {
                    $wa->useStyle('plg_content_litevidembed.lite-vimeo')
                       ->useScript('plg_content_litevidembed.lite-vimeo');
                    $style = 'lite-vimeo { margin: 0 auto; }';
                    $document->addStyleDeclaration($style);
                }
            }
            catch (\Exception $e)
            {
                $this->getApplication()->enqueueMessage($e->getMessage(), 'error');
                return;
            }
        }

        // Update module content if inside a module
        if ($context === 'com_modules.module')
        {
            $params->set('content', $text);
        }
        
        else
        {
            $article->text = $text;  // now update the article text with the processed text
        }
    }

    /**
     * Extracts the YouTube video ID from a given URL or raw ID string.
     *
     * @param   string  $url  The YouTube URL or direct video ID.
     * @return  string|false  Returns the video ID if matched, or false on failure.
     */
    protected function extractYoutubeId($url)
    {
        $patterns = [
        // youtube.com/watch?v=VIDEO_ID
        '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{10,12})/',
        // youtu.be/VIDEO_ID
        '/youtu\.be\/([a-zA-Z0-9_-]{10,12})/',
        // youtube.com/shorts/VIDEO_ID
        '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{10,12})/',
        // Just the video ID without URL
        '/^([a-zA-Z0-9_-]{10,12})$/' 
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