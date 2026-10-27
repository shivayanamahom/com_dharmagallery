<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
$kind='album'; $plural='albums'; $user=Factory::getApplication()->getIdentity();
$order=$this->state->get('list.ordering'); $direction=$this->state->get('list.direction'); $saveOrder=$order==='a.ordering' && $user->authorise('core.edit.state','com_dharmagallery');
$this->getDocument()->getWebAssetManager()->useScript('multiselect');
if ($saveOrder) { HTMLHelper::_('draggablelist.draggable'); }
?>


<form action="<?php echo Route::_('index.php?option=com_dharmagallery&view='.$plural); ?>" method="post" name="adminForm" id="adminForm">
<?php echo LayoutHelper::render('joomla.searchtools.default',['view'=>$this]); ?>
<?php if (!$this->items): ?><div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php else: ?>
<table class="table" id="galleryList"><caption class="visually-hidden"><?php echo Text::_('COM_DHARMAGALLERY_ALBUMS'); ?></caption>
<thead><tr><td class="w-1"><?php echo HTMLHelper::_('grid.checkall'); ?></td><th class="w-1"><?php echo HTMLHelper::_('searchtools.sort','JGRID_HEADING_ORDERING','a.ordering',$direction,$order); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th><?php echo HTMLHelper::_('searchtools.sort','JGLOBAL_TITLE','a.title',$direction,$order); ?></th><th><?php echo Text::_('COM_DHARMAGALLERY_PARENT'); ?></th><th><?php echo Text::_('COM_DHARMAGALLERY_MODE'); ?></th><th><?php echo Text::_('COM_DHARMAGALLERY_COUNT'); ?></th><th><?php echo Text::_('JFIELD_ACCESS_LABEL'); ?></th><th>ID</th></tr></thead>
<tbody<?php if ($saveOrder): ?> class="js-draggable" data-url="index.php?option=com_dharmagallery&amp;task=<?php echo $plural; ?>.saveOrderAjax&amp;tmpl=component&amp;<?php echo Session::getFormToken(); ?>=1" data-direction="<?php echo strtolower($direction); ?>" data-nested="true"<?php endif; ?>>
<?php foreach ($this->items as $i=>$item): $albumId=$kind==='album'?(int)$item->id:(int)$item->album_id; $canEdit=$user->authorise('core.edit',MediaHelper::asset($albumId)); ?>
<tr data-draggable-group="<?php echo (int)($kind==='album'?$item->parent_id:$item->album_id); ?>">
<td><?php echo HTMLHelper::_('grid.id',$i,$item->id); ?></td>
<td><span class="sortable-handler<?php echo $saveOrder?'':' inactive'; ?>"><span class="icon-ellipsis-v" aria-hidden="true"></span></span><?php if ($saveOrder): ?><input type="hidden" name="order[]" value="<?php echo (int)$item->ordering; ?>"><?php endif; ?></td>
<td><?php echo HTMLHelper::_('jgrid.published',$item->published,$i,$plural.'.',$user->authorise('core.edit.state',MediaHelper::asset($albumId))); ?></td>
<th scope="row"><?php if ($item->checked_out): echo HTMLHelper::_('jgrid.checkedout',$i,$item->created_by,$item->checked_out_time,$plural.'.'); endif; ?>
<?php echo str_repeat('<span class="gi" aria-hidden="true">&mdash;&nbsp;</span>', (int) ($item->level ?? 0)); ?>
<?php if ($canEdit): ?><a href="<?php echo Route::_('index.php?option=com_dharmagallery&task='.$kind.'.edit&id='.(int)$item->id); ?>"><?php echo $this->escape($item->title); ?></a><?php else: echo $this->escape($item->title); endif; ?>
<?php if ($kind==='album'): ?><small class="d-block text-muted"><?php echo str_repeat('<span class="gi" aria-hidden="true">&mdash;&nbsp;</span>', (int) ($item->level ?? 0)); echo Text::_('JFIELD_ALIAS_LABEL') . ': ' . $this->escape($item->alias); ?></small><?php else: ?><small class="d-block text-muted"><?php echo $this->escape($item->filename); ?></small><?php endif; ?></th>
<td><?php echo $this->escape($item->album_title ?: Text::_('JNONE')); ?></td><td><?php echo Text::_($item->mode === 'files' ? 'COM_DHARMAGALLERY_MODE_FILES' : 'COM_DHARMAGALLERY_MODE_GALLERY'); ?></td>
<td><?php echo $kind==='album'?(int)$item->item_count:Text::_($item->type==='image'?'COM_DHARMAGALLERY_IMAGE':'COM_DHARMAGALLERY_DOCUMENT'); ?></td>
<td><?php echo $this->escape($item->access_title); ?></td><td><?php echo (int)$item->id; ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?>
<?php echo $this->pagination->getListFooter(); ?>
<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0"><?php echo HTMLHelper::_('form.token'); ?></form>
