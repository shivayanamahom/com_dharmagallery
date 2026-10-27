<?php
namespace Dharma\Component\Gallery\Site\View\Album;
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
  
  $id=$input->getInt('id',(int)($menuParams?->get('album_id',0)??0)); $this->album=$this->getModel()->getAlbum($id);
  if (!$this->album) { throw new \RuntimeException(Text::_('COM_DHARMAGALLERY_NOT_FOUND'),404); }
  $this->params=json_decode($this->album->params?:'{}',true)?:[]; $this->params['mode']=$this->album->mode ?? 'gallery';
  $model=$factory->createModel('Items','Site',['ignore_request'=>false]); $model->getState(); $model->setState('filter.album_id',$id);
  $this->items=$model->getItems(); $this->pagination=$model->getPagination();
  $children=$factory->createModel('Albums','Site',['ignore_request'=>true]); $children->setState('filter.parent_id',$id); $children->setState('list.limit',0); $this->children=$children->getItems();
  $menuRoot = $menu && ($menu->query['option'] ?? '') === 'com_dharmagallery'
      ? (($menu->query['view'] ?? '') === 'albums' ? H::menuParentId($menu) : H::menuAlbumId($menu)) : 0;
  foreach ($this->getModel()->getBreadcrumbs($id) as $crumb) {
      if ((int) $crumb->id !== $id && (int) $crumb->id !== $menuRoot) {
          $app->getPathway()->addItem($crumb->title, H::route('album', (int) $crumb->id));
      }
  }
  $this->pageHeading=$this->album->title; $this->getDocument()->setDescription($this->album->metadesc??'');

  $sameMenu=$menu && ($menu->query['option']??'')==='com_dharmagallery' && ($menu->query['view']??'')==='album' && (!(int)$input->getInt('id') || (int)($menu->query['id']??0)===$input->getInt('id'));
  if ($sameMenu) { $this->pageHeading=$menuParams->get('page_heading',$this->pageHeading); }
  if (!$sameMenu) { $app->getPathway()->addItem($this->pageHeading); }
  $this->getDocument()->setTitle($sameMenu?$menuParams->get('page_title',$this->pageHeading):$this->pageHeading);
  H::assets($this->getDocument(),$this->params['mode']==='gallery' && (int)($this->params['lightbox']??1)===1 && count($this->items)>0);
  return parent::display($tpl);
 }
}
