<?php

defined('_JEXEC') or die;

use Dharma\Plugin\Content\DharmaGallery\Extension\DharmaGallery;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new DharmaGallery(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('content', 'dharmagallery')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
