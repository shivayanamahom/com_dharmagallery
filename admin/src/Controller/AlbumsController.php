<?php
namespace Dharma\Component\Gallery\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
class AlbumsController extends AdminController
{
 protected $option='com_dharmagallery';
 protected $text_prefix='COM_DHARMAGALLERY';
 public function getModel($name='Album',$prefix='Administrator',$config=['ignore_request'=>true]) { return parent::getModel($name,$prefix,$config); }
}
