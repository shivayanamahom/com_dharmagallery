<?php
namespace Dharma\Component\Gallery\Administrator\Model;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
abstract class RecordModel extends AdminModel
{
 protected $option='com_dharmagallery';
 protected string $record='album';
 protected function loadFormData() { return Factory::getApplication()->getUserState('com_dharmagallery.edit.'.$this->record.'.data',[]) ?: $this->getItem(); }
 public function getForm($data=[],$loadData=true)
 {
  $form=$this->loadForm('com_dharmagallery.'.$this->record,$this->record,['control'=>'jform','load_data'=>$loadData]);
  $item=$this->getItem(); $albumId=$this->record==='album' ? (int)($item->id??0) : (int)($item->album_id??0);
  if ($form && !$this->canEditState($item ?: (object)[])) {
   foreach (['published','access','ordering'] as $field) { $form->setFieldAttribute($field,'disabled','true'); $form->setFieldAttribute($field,'filter','unset'); }
  }
  return $form;
 }
 protected function canEditState($record) { return Factory::getApplication()->getIdentity()->authorise('core.edit.state',MediaHelper::asset((int)($this->record==='album'?($record->id??0):($record->album_id??0)))); }
 protected function canDelete($record) { return (int)$record->published===-2 && Factory::getApplication()->getIdentity()->authorise('core.delete',MediaHelper::asset((int)($this->record==='album'?$record->id:$record->album_id))); }
 protected function prepareTable($table)
 {
  $user=Factory::getApplication()->getIdentity(); $now=Factory::getDate()->toSql();
  if (!(int)$table->id) { $table->created=$now; $table->created_by=$user->id; }
  $table->modified=$now; $table->modified_by=$user->id;
 }
 public function save($data)
 {
  // Never accept creator or checkout metadata from a posted form.
  unset($data['created'],$data['created_by'],$data['modified'],$data['modified_by'],$data['checked_out'],$data['checked_out_time'],$data['asset_id']);
  $item=!empty($data['id']) ? $this->getItem((int)$data['id']) : null;
  if (!$this->canEditState($item ?: (object)['album_id'=>(int)($data['album_id']??0),'catid'=>(int)($data['catid']??0)])) { foreach (['published','access','ordering'] as $key) { unset($data[$key]); } }
  return parent::save($data);
 }
}
