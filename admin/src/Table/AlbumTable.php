<?php
namespace Dharma\Component\Gallery\Administrator\Table;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
class AlbumTable extends Table
{
 protected $_jsonEncode=['params'];
 public function __construct(DatabaseInterface $db) { parent::__construct('#__dharmagallery_albums','id',$db); $this->setColumnAlias('state','published'); }
 protected function _getAssetName() { return 'com_dharmagallery.album.'.(int)$this->id; }
 protected function _getAssetTitle() { return $this->title; }
 protected function _getAssetParentId(?Table $table=null,$id=null) { $a=self::getInstance('Asset'); $a->loadByName('com_dharmagallery'); return (int)$a->id ?: 1; }
 public function check()
 {
  $this->title=trim($this->title ?? ''); if ($this->title==='') { throw new \InvalidArgumentException('JGLOBAL_TITLE'); }
  $this->alias=OutputFilter::stringURLSafe($this->alias ?: $this->title); if ($this->alias==='') { $this->alias='album-'.bin2hex(random_bytes(4)); }
  $db=$this->getDatabase(); $base=$this->alias; $suffix=1;
  while ($db->setQuery($db->getQuery(true)->select('id')->from('#__dharmagallery_albums')->where('alias='.$db->quote($this->alias))->where('id!='.(int)$this->id))->loadResult()) { $this->alias=$base.'-'.++$suffix; }
  $seen=[(int)$this->id]; $parent=(int)$this->parent_id;
  while ($parent>0) { if (in_array($parent,$seen,true)) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_PARENT_CYCLE'); } $seen[]=$parent; $row=$db->setQuery('SELECT parent_id FROM #__dharmagallery_albums WHERE id='.$parent)->loadObject(); if (!$row) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_ALBUM'); } $parent=(int)$row->parent_id; }
  if (!$this->created) { $this->created=Factory::getDate()->toSql(); }
  if (!$this->ordering) { $this->ordering=$this->getNextOrder('parent_id='.(int)$this->parent_id); }
  return parent::check();
 }
}
