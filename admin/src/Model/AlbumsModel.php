<?php
namespace Dharma\Component\Gallery\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
class AlbumsModel extends ListModel
{
 protected $option='com_dharmagallery';
 public function __construct($config=[]) { $config['filter_fields']=['a.id','a.title','a.published','a.ordering','a.access']; parent::__construct($config); }
 protected function populateState($ordering='a.ordering',$direction='ASC') {
  foreach (['search'=>'string','published'=>'string','album_id'=>'int','type'=>'cmd','access'=>'int'] as $name=>$filter) { $this->setState('filter.'.$name,$this->getUserStateFromRequest($this->context.'.filter.'.$name,'filter_'.$name,$name==='published'?'':null,$filter)); }
  parent::populateState($ordering,$direction);
 }
 protected function getListQuery() {
  $db=$this->getDatabase(); $q=$db->getQuery(true)->select('a.*')->from('#__dharmagallery_albums AS a');
  $q->select('p.title AS album_title')->leftJoin('#__dharmagallery_albums AS p ON p.id=a.parent_id');
  $q->select('v.title AS access_title')->leftJoin('#__viewlevels AS v ON v.id=a.access');
  $q->select('(SELECT COUNT(*) FROM #__dharmagallery_photos AS f WHERE f.album_id=a.id) AS item_count');
  $search=trim((string)$this->getState('filter.search')); if ($search!=='') { if (str_starts_with($search,'id:')) { $q->where('a.id='.(int)substr($search,3)); } else { $q->where('a.title LIKE '.$db->quote('%'.$db->escape($search,true).'%',false)); } }
  $state=$this->getState('filter.published'); $q->where(is_numeric($state)?'a.published='.(int)$state:'a.published IN (0,1,2)');
  $album=(int)$this->getState('filter.album_id'); if ($album) { $q->where('a.parent_id='.$album); }
  $access=(int)$this->getState('filter.access'); if ($access) { $q->where('a.access='.$access); }
  
  $order=$this->getState('list.ordering','a.ordering'); if (!in_array($order,['a.id','a.title','a.published','a.ordering','a.access'],true)) { $order='a.ordering'; }
  return $q->order($order.' '.($this->getState('list.direction')==='DESC'?'DESC':'ASC'))->order('a.id ASC');
 }
}
