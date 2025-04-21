<?php
/**
 * @package    Lite Vid Embed Plugin
 * @license    GNU General Public License version 2
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Naftee\Plugin\Content\Litevidembed\Extension\Litevidembed;

return new class() implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {

                $config = (array) PluginHelper::getPlugin('content', 'litevidembed');
                $subject = $container->get(DispatcherInterface::class);
                $app = Factory::getApplication();

                $plugin = new Litevidembed($subject, $config);
                $plugin->setApplication($app);

                return $plugin;
            }
        );
    }
};
