<?php
namespace Dharma\Component\Gallery\Administrator\View\Albums;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
class HtmlView extends BaseHtmlView
{
 public $items; public $state; public $pagination; public $filterForm; public $activeFilters;
 public function display($tpl=null) {
  $model=$this->getModel(); $this->items=$model->getItems(); $this->state=$model->getState(); $this->pagination=$model->getPagination(); $this->filterForm=$model->getFilterForm(); $this->activeFilters=$model->getActiveFilters();
  if ($errors=$model->getErrors()) { throw new \RuntimeException(implode("\n",$errors),500); }
  // Окно выбора альбома для кнопки редактора открывается без панели кнопок.
  if ($this->getLayout()==='modal') { return parent::display($tpl); }
  $user=Factory::getApplication()->getIdentity(); ToolbarHelper::title(Text::_('COM_DHARMAGALLERY_ALBUMS'),'images');
  if ($user->authorise('core.create','com_dharmagallery')) { ToolbarHelper::addNew('album.add'); }
  if ($user->authorise('core.edit.state','com_dharmagallery')) { ToolbarHelper::publish('albums.publish'); ToolbarHelper::unpublish('albums.unpublish'); ToolbarHelper::trash('albums.trash'); }
  if ((string)$this->state->get('filter.published')==='-2' && $user->authorise('core.delete','com_dharmagallery')) { ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE','albums.delete'); }
  ToolbarHelper::checkin('albums.checkin');
  if ($user->authorise('core.options','com_dharmagallery')) { ToolbarHelper::preferences('com_dharmagallery'); }
  return parent::display($tpl);
 }
}
