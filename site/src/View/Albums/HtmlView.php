<?php
namespace Dharma\Component\Gallery\Site\View\Albums;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
class HtmlView extends BaseHtmlView
{
 public $items=[]; public $children=[]; public $pagination; public $album; public $category; public $params=[]; public $pageHeading; public $parent;
 public function display($tpl=null) {
  $app=Factory::getApplication(); $input=$app->getInput(); $menu=$app->getMenu()->getActive(); $menuParams=$menu && ($menu->query['option']??'')==='com_dharmagallery' ? $menu->getParams() : null; $factory=$app->bootComponent('com_dharmagallery')->getMVCFactory();
  
  $model=$this->getModel(); $parent=(int)$model->getState('filter.parent_id');
  if ($parent && !H::visibleAlbum($parent)) { throw new \RuntimeException(Text::_('COM_DHARMAGALLERY_NOT_FOUND'),404); }
  $this->items=$model->getItems(); $this->pagination=$model->getPagination(); $this->pageHeading=$parent?(H::albums()[$parent]->title??''):Text::_('COM_DHARMAGALLERY_ALBUMS');

  $sameMenu=$menu && ($menu->query['option']??'')==='com_dharmagallery' && ($menu->query['view']??'')==='albums' && (!(int)$input->getInt('id') || (int)($menu->query['id']??0)===$input->getInt('id'));
  if ($sameMenu) { $this->pageHeading=$menuParams->get('page_heading',$this->pageHeading); }
  $app->getPathway()->addItem($this->pageHeading);
  $this->getDocument()->setTitle($sameMenu?$menuParams->get('page_title',$this->pageHeading):$this->pageHeading);
  H::assets($this->getDocument(),count(array_filter($this->items,fn($i)=>($i->type??'')==='image'))>0);
  return parent::display($tpl);
 }
}
