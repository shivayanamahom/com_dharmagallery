<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
$user = Factory::getApplication()->getIdentity();
$id = (int) $this->item->id;
$mode = $this->item->mode ?? 'gallery';
$canCreate = $user->authorise('core.create', MediaHelper::asset($id));
$wa = $this->getDocument()->getWebAssetManager();
$wa->getRegistry()->addExtensionRegistryFile('com_dharmagallery');
$wa->useScript('com_dharmagallery.upload');
foreach (['UPLOADING', 'UPLOAD_DONE', 'UPLOAD_FAILED', 'SAVE_CONTENTS'] as $key) { Text::script('COM_DHARMAGALLERY_' . $key); }
?>
<form action="<?php echo Route::_('index.php?option=com_dharmagallery&view=album&layout=edit&id=' . $id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
<?php echo LayoutHelper::render('joomla.edit.title_alias', $this); ?>
<div class="row g-3">
 <div class="col-lg-9">
  <div class="main-card">
   <?php echo HTMLHelper::_('uitab.startTabSet', 'galleryTabs', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'galleryTabs', 'details', Text::_('JDETAILS')); ?>
   <?php echo $this->form->renderField('description'); ?>
   <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'galleryTabs', 'contents', Text::_($id && $mode === 'files' ? 'COM_DHARMAGALLERY_MODE_FILES' : 'COM_DHARMAGALLERY_CONTENTS')); ?>
   <?php if (!$id): ?>
    <div class="alert alert-info"><?php echo Text::_('COM_DHARMAGALLERY_SAVE_FIRST'); ?></div>
   <?php else: ?>
    <section data-dg-contents data-album="<?php echo $id; ?>" data-mode="<?php echo $this->escape($mode); ?>" data-url="<?php echo Route::_('index.php?option=com_dharmagallery&task=album.upload&format=json', false); ?>" data-token="<?php echo Session::getFormToken(); ?>">
     <?php if ($canCreate): ?>
      <div class="card card-body mb-3">
       <h2 class="h4"><?php echo Text::_('COM_DHARMAGALLERY_UPLOAD'); ?></h2>
       <p><?php echo Text::_('COM_DHARMAGALLERY_UPLOAD_HELP'); ?></p>
       <label class="form-label" for="dg-upload-files"><?php echo Text::_('COM_DHARMAGALLERY_SELECT_FILES'); ?></label>
       <input id="dg-upload-files" data-dg-files class="form-control" type="file" multiple accept="<?php echo $mode === 'gallery' ? '.jpg,.jpeg,.png,.webp,.gif' : '.' . implode(',.', array_keys(MediaHelper::TYPES)); ?>">
       <div class="border rounded p-3 mt-3 text-center" data-dg-drop tabindex="0" role="button"><?php echo Text::_('COM_DHARMAGALLERY_DROP_FILES'); ?></div>
       <div class="mt-3"><button type="button" class="btn btn-primary" data-dg-start><span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_DHARMAGALLERY_UPLOAD'); ?></button></div>
       <p class="mt-2 mb-0" role="status" aria-live="polite" data-dg-status></p><ul class="mt-2" data-dg-results></ul>
      </div>
     <?php endif; ?>
     <p class="text-muted"><?php echo Text::_('COM_DHARMAGALLERY_CONTENTS_HELP'); ?></p>
     <?php echo $this->form->renderField('mediaitems'); ?>
    </section>
   <?php endif; ?>
   <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'galleryTabs', 'children', Text::_('COM_DHARMAGALLERY_CHILDREN')); ?>
   <?php if (!$id): ?><div class="alert alert-info"><?php echo Text::_('COM_DHARMAGALLERY_SAVE_FIRST'); ?></div>
   <?php else: ?>
    <?php if ($canCreate): ?><p><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_dharmagallery&task=album.add&parent_id=' . $id); ?>"><?php echo Text::_('COM_DHARMAGALLERY_ADD_CHILD'); ?></a></p><?php endif; ?>
    <?php if (!$this->children): ?><p><?php echo Text::_('COM_DHARMAGALLERY_NO_CHILDREN'); ?></p><?php else: ?>
     <ul class="list-group"><?php foreach ($this->children as $child): ?><li class="list-group-item d-flex justify-content-between gap-2"><a href="<?php echo Route::_('index.php?option=com_dharmagallery&task=album.edit&id=' . (int) $child->id); ?>"><?php echo $this->escape($child->title); ?></a><span class="badge bg-secondary"><?php echo Text::_($child->mode === 'files' ? 'COM_DHARMAGALLERY_MODE_FILES' : 'COM_DHARMAGALLERY_MODE_GALLERY'); ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
   <?php endif; ?>
   <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php echo HTMLHelper::_('uitab.addTab', 'galleryTabs', 'display', Text::_('COM_DHARMAGALLERY_SETTINGS')); ?>
   <?php echo $this->form->renderFieldset('display'); ?>
   <?php echo HTMLHelper::_('uitab.endTab'); ?>
   <?php if ($user->authorise('core.admin', 'com_dharmagallery')): ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'galleryTabs', 'permissions', Text::_('JCONFIG_PERMISSIONS_LABEL')); echo $this->form->renderFieldset('permissions'); echo HTMLHelper::_('uitab.endTab'); ?>
   <?php endif; ?>
   <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
  </div>
 </div>
 <aside class="col-lg-3" aria-label="<?php echo Text::_('COM_DHARMAGALLERY_ALBUM_OPTIONS'); ?>">
  <div class="options-form form-vertical card card-body"><?php echo $this->form->renderFieldset('sidebar'); ?></div>
 </aside>
</div>
<?php echo $this->form->renderField('id'); echo $this->form->renderField('mediaitems_complete'); ?>
<input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
