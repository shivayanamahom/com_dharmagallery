<?php
namespace Dharma\Component\Gallery\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper;
class ItemsModel extends ListModel
{
 protected $option='com_dharmagallery';
 protected function populateState($ordering=null,$direction=null) {
  $app=Factory::getApplication(); $limit=max(1,min(100,(int)ComponentHelper::getParams('com_dharmagallery')->get('items_per_page',24)));
  $this->setState('list.limit',$limit); $this->setState('list.start',max(0,(int)(floor($app->getInput()->getInt('limitstart',0)/$limit)*$limit)));
 }

 protected function getListQuery()
 {
     $album = (int) $this->getState('filter.album_id');
     return $this->getDatabase()->getQuery(true)
         ->select('a.*')->from('#__dharmagallery_photos AS a')
         ->where('a.album_id=' . ($album > 0 && GalleryHelper::visibleAlbum($album) ? $album : -1))
         ->where('a.published=1')->where('a.access IN (' . GalleryHelper::levels() . ')')
         ->order('a.ordering ASC')->order('a.id ASC');
 }
}
