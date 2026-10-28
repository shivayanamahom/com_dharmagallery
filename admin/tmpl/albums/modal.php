<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
/** Окно выбора альбома для кнопки редактора: кнопки «Вставить» передают код родительскому окну (modal-content-select). */
$editor = Factory::getApplication()->getInput()->getCmd('editor', '');
$this->getDocument()->getWebAssetManager()->useScript('core')->useScript('modal-content-select');
$states = [1 => 'icon-publish', 0 => 'icon-unpublish', 2 => 'icon-archive', -2 => 'icon-trash'];
?>
<div class="container-popup">
<form action="<?php echo Route::_('index.php?option=com_dharmagallery&view=albums&layout=modal&tmpl=component&editor=' . $editor); ?>" method="post" name="adminForm" id="adminForm">
<p class="text-muted"><?php echo Text::_('COM_DHARMAGALLERY_PICK_ALBUM'); ?></p>
<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
<?php if (!$this->items): ?><div class="alert alert-info"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php else: ?>
<table class="table table-sm"><caption class="visually-hidden"><?php echo Text::_('COM_DHARMAGALLERY_ALBUMS'); ?></caption>
<thead><tr><th class="w-1 text-center"><?php echo Text::_('JSTATUS'); ?></th><th><?php echo Text::_('JGLOBAL_TITLE'); ?></th><th><?php echo Text::_('COM_DHARMAGALLERY_TYPE'); ?></th><th class="w-1"><?php echo Text::_('JGRID_HEADING_ID'); ?></th><th class="w-1"></th></tr></thead>
<tbody>
<?php foreach ($this->items as $item): $code = '{dharmagallery id=' . (int) $item->id . '}'; ?>
<tr>
<td class="text-center"><span class="<?php echo $states[(int) $item->published] ?? 'icon-unpublish'; ?>" aria-hidden="true"></span></td>
<th scope="row"><?php echo str_repeat('<span class="gi" aria-hidden="true">&mdash;&nbsp;</span>', (int) ($item->level ?? 0)) . $this->escape($item->title); ?></th>
<td><?php echo Text::_($item->mode === 'files' ? 'COM_DHARMAGALLERY_MODE_FILES' : 'COM_DHARMAGALLERY_MODE_GALLERY'); ?></td>
<td><?php echo (int) $item->id; ?></td>
<td><button type="button" class="btn btn-primary btn-sm" data-content-select data-content-type="com_dharmagallery.album" data-id="<?php echo (int) $item->id; ?>" data-title="<?php echo $this->escape($item->title); ?>" data-html="<?php echo $this->escape($code); ?>"><?php echo Text::_('COM_DHARMAGALLERY_INSERT'); ?></button></td>
</tr>
<?php endforeach; ?>
</tbody></table><?php endif; ?>
<?php echo $this->pagination->getListFooter(); ?>
<input type="hidden" name="task" value=""><input type="hidden" name="boxchecked" value="0">
</form>
</div>
