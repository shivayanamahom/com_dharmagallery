<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Language\Text;
extract($displayData, EXTR_SKIP);
$path = (string) $form->getValue('path', null, '');
$preview = ''; $extension = '';
if ($path !== '') {
 try {
  $path = MediaHelper::path($path); $extension = strtoupper(pathinfo($path, PATHINFO_EXTENSION));
  if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) { $preview = MediaHelper::url($path); }
 } catch (\Throwable $e) {}
}
?>
<div class="subform-repeatable-group col" data-base-name="<?php echo $this->escape($basegroup); ?>" data-group="<?php echo $this->escape($group); ?>">
 <div class="card h-100 dg-media-card">
  <div class="dg-media-preview" data-dg-preview>
   <img data-dg-thumb<?php echo $preview ? ' src="' . $this->escape($preview) . '"' : ''; ?> alt="" loading="lazy" decoding="async" class="<?php echo $preview ? '' : 'd-none'; ?>">
   <div data-dg-file-icon class="dg-file-preview<?php echo $preview ? ' d-none' : ''; ?>"><span class="icon-file" aria-hidden="true"></span><span data-dg-extension><?php echo $this->escape($extension); ?></span></div>
  </div>
  <div class="card-body p-2">
   <?php echo $form->renderField('id'); echo $form->renderField('path'); echo $form->renderField('title'); ?>
   <details class="dg-card-details"><summary><?php echo Text::_('COM_DHARMAGALLERY_ITEM_DETAILS'); ?></summary>
    <?php echo $form->renderField('alt'); echo $form->renderField('description'); echo $form->renderField('published'); ?>
   </details>
  </div>
  <div class="btn-toolbar dg-card-actions" role="toolbar" aria-label="<?php echo Text::_('COM_DHARMAGALLERY_ITEM_ACTIONS'); ?>"><div class="btn-group w-100">
   <?php if (!empty($buttons['add'])): ?><button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>"><span class="icon-plus" aria-hidden="true"></span></button><?php endif; ?>
   <?php if (!empty($buttons['remove'])): ?><button type="button" class="group-remove btn btn-sm btn-danger" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>"><span class="icon-minus" aria-hidden="true"></span></button><?php endif; ?>
   <?php if (!empty($buttons['move'])): ?><button type="button" class="group-move btn btn-sm btn-primary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>"><span class="icon-arrows-alt" aria-hidden="true"></span></button><button type="button" class="group-move-up btn btn-sm btn-secondary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE_UP'); ?>"><span class="icon-chevron-up" aria-hidden="true"></span></button><button type="button" class="group-move-down btn btn-sm btn-secondary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE_DOWN'); ?>"><span class="icon-chevron-down" aria-hidden="true"></span></button><?php endif; ?>
  </div></div>
 </div>
</div>
