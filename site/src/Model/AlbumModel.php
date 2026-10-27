<?php
namespace Dharma\Component\Gallery\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper;
class AlbumModel extends BaseDatabaseModel
{
 protected $option='com_dharmagallery';
 public function getAlbum($id): ?object {
  $id=(int)$id; if ($id<=0 || !GalleryHelper::visibleAlbum($id)) { return null; }
  return $this->getDatabase()->setQuery('SELECT * FROM #__dharmagallery_albums WHERE id='.$id)->loadObject() ?: null;
 }
 public function getAlbumByAlias(string $alias): ?object { $id=(int)$this->getDatabase()->setQuery('SELECT id FROM #__dharmagallery_albums WHERE alias='.$this->getDatabase()->quote($alias))->loadResult(); return $this->getAlbum($id); }
 public function getBreadcrumbs(int $id): array { $rows=GalleryHelper::albums(); $crumbs=[]; $seen=[]; while ($id && isset($rows[$id]) && !isset($seen[$id])) { $seen[$id]=true; array_unshift($crumbs,$rows[$id]); $id=(int)$rows[$id]->parent_id; } return $crumbs; }
}
