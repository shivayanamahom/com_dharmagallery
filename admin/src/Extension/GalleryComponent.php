<?php
namespace Dharma\Component\Gallery\Administrator\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
class GalleryComponent extends MVCComponent implements RouterServiceInterface
{
    use RouterServiceTrait;
}
