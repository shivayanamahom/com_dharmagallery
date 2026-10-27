<?php

defined('_JEXEC') or die;

use Dharma\Component\Gallery\Administrator\Extension\GalleryComponent;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {

	public function register(Container $container): void
	{
		$container->registerServiceProvider(new MVCFactory('\\Dharma\\Component\\Gallery'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Dharma\\Component\\Gallery'));
		$container->registerServiceProvider(new RouterFactory('\\Dharma\\Component\\Gallery'));

		$container->set(
			ComponentInterface::class,
			function (Container $container) {
				$component = new GalleryComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
