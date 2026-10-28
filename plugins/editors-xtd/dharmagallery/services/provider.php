<?php

defined('_JEXEC') or die;

use Dharma\Plugin\EditorsXtd\DharmaGallery\Extension\DharmaGallery;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(DharmaGallery::class, function (Container $container) {
                $plugin = new DharmaGallery((array) PluginHelper::getPlugin('editors-xtd', 'dharmagallery'));
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
