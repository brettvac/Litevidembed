<?php
/**
 * @package    Litevidembed
 * @version    1.3
 * @license    GNU General Public License version 2
 */
namespace Naftee\Plugin\Content\Litevidembed\Extension;

\defined('_JEXEC') or die; // No direct access

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Log\Log;

/**
 * Litevidembed plugin to embed lightweight YouTube and Vimeo videos using shortcodes.
 */
class Litevidembed extends CMSPlugin implements SubscriberInterface
{
    protected $loadYoutube = false;
    protected $loadVimeo = false;

    /**
     * Maps Joomla events to the methods this plugin runs when Joomla triggers those events.
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
    public function replaceVideoShortcodes(ContentPrepareEvent $event)
    {
        if (!$this->getApplication()->isClient('site'))
        {
            return; // Exit if this request is from the backend (administrator)
        }
    
        // Use the concrete getter methods
        $context = $event->getContext();
        $article = $event->getItem();
        $params  = $event->getParams();

        // Skip if the context is the indexer
        if ($context === 'com_finder.indexer')
        {
            return;
        }

        // Initialize text to process
        $text = null;

        // Handle different contexts and content properties
        switch ($context) 
          {
          case 'com_content.article':
          case 'com_content.category':
          case 'com_content.featured':
             $text = $article->text;
             break;

        case 'com_modules.module':
             $text = $params->get('content', '');
             break;

        default:
           $text = $article->text; // Fallback for any other context
           break;
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

        $text = preg_replace_callback('/\{(youtube|vimeo)\}(.*?)\{\/\1\}/is',[$this, 'processVideoShortcode'],$text);

        // Load assets only if needed
        if ($this->loadYoutube || $this->loadVimeo)
        {
            $document = $this->getApplication()->getDocument();
            $wa = $document->getWebAssetManager();

            try
            {
                $wa->getRegistry()->addExtensionRegistryFile('plg_content_litevidembed');

                if ($this->loadYoutube)
                {
                    $wa->useStyle('plg_content_litevidembed.lite-youtube')
                       ->useScript('plg_content_litevidembed.lite-youtube');
                    $style = 'lite-youtube { margin: 0 auto; }';
                    $document->addStyleDeclaration($style);
                }

                if ($this->loadVimeo)
                {
                    $wa->useStyle('plg_content_litevidembed.lite-vimeo')
                       ->useScript('plg_content_litevidembed.lite-vimeo');
                    $style = 'lite-vimeo { margin: 0 auto; }';
                    $document->addStyleDeclaration($style);
                }
            }
            catch (\Exception $e)
            {
                Log::add($e->getMessage(), Log::ERROR, 'litevidembed');

                if (JDEBUG)
                {
                    Log::add($e->getMessage(), Log::DEBUG, 'litevidembed');
                }

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
     * Replaces a video shortcode with a lite embed element.
     *
     * @param   array  $matches  The regex matches.
     *
     * @return  string
     */
    protected function processVideoShortcode(array $matches): string
    {
        $platform = strtolower($matches[1]);
        $tagContent = strip_tags(trim($matches[2]));

        $width = null;
        $videoUrl = $tagContent;

        // Check for width of video in shortcode
        if (strpos($tagContent, '|') !== false)
        {
            [$videoUrl, $customWidth] = array_map('trim', explode('|', $tagContent, 2));

            $videoUrl = htmlspecialchars_decode($videoUrl, ENT_QUOTES);

            if (is_numeric($customWidth) && (int) $customWidth <= 720)
            {
                $width = (int) $customWidth . 'px';
            }
        }

        $videoId = null;
        $tagName = null;

        switch ($platform)
        {
            case 'youtube':
                $videoId = $this->extractYoutubeId($videoUrl);

                if ($videoId)
                {
                    $tagName = 'lite-youtube';
                    $this->loadYoutube = true;
                }
                break;

            case 'vimeo':
                $videoId = $this->extractVimeoId($videoUrl);

                if ($videoId)
                {
                    $tagName = 'lite-vimeo';
                    $this->loadVimeo = true;
                }
                break;
        }

        // Leave invalid shortcodes untouched
        if (!$videoId || !$tagName)
        {
            return $matches[0];
        }

        $style = $width ? " style=\"width: {$width};\"" : '';

        return "<{$tagName} videoid=\"{$videoId}\"{$style}></{$tagName}>";
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