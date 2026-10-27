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

 private ?array $treeItems = null;

 /** Группировка дерева выполняется до пагинации, чтобы подальбомы не смешивались с соседними ветками. */
 private function tree(): array
 {
     if ($this->treeItems !== null) { return $this->treeItems; }
     $this->getState();
     $db = $this->getDatabase();
     $items = $db->setQuery($this->getListQuery())->loadObjectList('id');
     $parents = $db->setQuery('SELECT id,parent_id FROM #__dharmagallery_albums')->loadObjectList('id');
     $children = [];
     foreach ($items as $item) { $children[(int) $item->parent_id][] = $item; }
     $flat = []; $seen = [];
     $append = function ($item) use (&$append, &$flat, &$seen, $children, $parents): void {
         $id = (int) $item->id;
         if (isset($seen[$id])) { return; }
         $seen[$id] = true;
         $level = 0; $parent = (int) $item->parent_id; $chain = [$id => true];
         while ($parent > 0 && isset($parents[$parent]) && !isset($chain[$parent])) {
             $chain[$parent] = true; $level++; $parent = (int) $parents[$parent]->parent_id;
         }
         $item->level = $level;
         $flat[] = $item;
         foreach ($children[$id] ?? [] as $child) { $append($child); }
     };
     foreach ($items as $item) { if (!(int) $item->parent_id || !isset($items[(int) $item->parent_id])) { $append($item); } }
     foreach ($items as $item) { $append($item); }
     return $this->treeItems = $flat;
 }

 public function getItems()
 {
     $items = $this->tree();
     $limit = max(0, (int) $this->getState('list.limit'));
     $start = max(0, (int) $this->getState('list.start'));
     if ($start >= count($items)) { $start = 0; $this->setState('list.start', 0); }
     return $limit ? array_slice($items, $start, $limit) : $items;
 }

 public function getTotal() { return count($this->tree()); }
}
