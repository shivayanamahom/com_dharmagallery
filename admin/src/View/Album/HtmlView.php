<?php
namespace Dharma\Component\Gallery\Administrator\View\Album;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
class HtmlView extends BaseHtmlView
{
 public $item; public $form; public $children = [];
 public function display($tpl=null) {
  $model=$this->getModel(); $this->item=$model->getItem(); $this->form=$model->getForm(); $this->children=$this->item->id ? $model->getChildren((int)$this->item->id) : [];
  if ($errors=$model->getErrors()) { throw new \RuntimeException(implode("\n",$errors),500); }
  ToolbarHelper::title(Text::_('COM_DHARMAGALLERY_ALBUM'),'images');
  ToolbarHelper::apply('album.apply'); ToolbarHelper::save('album.save'); ToolbarHelper::save2new('album.save2new'); ToolbarHelper::cancel('album.cancel');
  return parent::display($tpl);
 }
}
