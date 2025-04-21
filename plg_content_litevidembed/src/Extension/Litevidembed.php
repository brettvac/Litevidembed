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

        if ($context === 'com_finder.indexer')
        {
            return; // Exit if the content is being processed by Joomla's Smart Search (Finder) indexer
        }

        if ($context === 'com_modules.module')
        {
            $text = $params->get('content', '');
        }
        elseif (in_array($context, ['com_content.article', 'com_content.featured', 'com_content.category']))
        {
            if (!isset($article->text))
            {
                return;
            }
            $text = &$article->text;
        }
        else
        {
            return;
        }

        /*
         * Check if either YouTube or Vimeo shortcodes exist in the text;
         * exit early if neither is found
         */
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
                    $url = substr($text, $start + $tagLength, $end - $start - $tagLength);
                    $videoId = $platform === 'youtube' ? $this->extractYoutubeId($url) : $this->extractVimeoId($url);

                    if ($videoId)
                    {
                        $replacement = $platform === 'youtube' ?
                            "<lite-youtube videoid=\"{$videoId}\"></lite-youtube>" :
                            "<lite-vimeo videoid=\"{$videoId}\"></lite-vimeo>";

                        $text = substr_replace($text, $replacement, $start, $end - $start + strlen($closingTag));
                        $offset = $start + strlen($replacement);

                        if ($platform === 'youtube')
                        {
                            $loadYoutube = true;
                        }
                        else
                        {
                            $loadVimeo = true;
                        }
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