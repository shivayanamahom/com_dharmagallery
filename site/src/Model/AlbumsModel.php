<?php
namespace Dharma\Component\Gallery\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper;
class AlbumsModel extends ListModel
{
 protected $option='com_dharmagallery';
 protected function populateState($ordering=null,$direction=null) {
  $app=Factory::getApplication(); $menu=$app->getMenu()->getActive();
  $menuParent=$menu && ($menu->query['option']??'')==='com_dharmagallery' ? GalleryHelper::menuParentId($menu) : 0;
  $this->setState('filter.parent_id',$app->getInput()->getInt('parent_id',$menuParent));
  $limit=max(1,min(100,(int)ComponentHelper::getParams('com_dharmagallery')->get('albums_per_page',12)));
  $this->setState('list.limit',$limit); $this->setState('list.start',max(0,(int)(floor($app->getInput()->getInt('limitstart',0)/$limit)*$limit)));
 }
 protected function getListQuery() {
  $q=$this->getDatabase()->getQuery(true)->select('a.*')->from('#__dharmagallery_albums AS a')->where('a.parent_id='.(int)$this->getState('filter.parent_id'))->where('a.published=1')->where('a.access IN ('.GalleryHelper::levels().')'.GalleryHelper::language('a.language'))->order('a.ordering ASC')->order('a.id ASC');
  $first = $this->getDatabase()->getQuery(true)->select('p.id')->from('#__dharmagallery_photos AS p')
      ->where('p.album_id=a.id')->where("p.type='image'")->where('p.published=1')
      ->where('p.access IN (' . GalleryHelper::levels() . ')')->order('p.ordering ASC')->order('p.id ASC')->setLimit(1);
  $q->select(['cover_photo.id AS first_photo_id', 'cover_photo.path AS first_photo_path', 'cover_photo.filename AS first_photo_filename'])
      ->leftJoin('#__dharmagallery_photos AS cover_photo ON cover_photo.id=(' . $first . ')');
  return $q;
 }
 public function getAlbum(int $id): ?object { $m=$this->getMVCFactory()->createModel('Album','Site',['ignore_request'=>true]); return $m->getAlbum($id); }
}
