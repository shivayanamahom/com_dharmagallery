<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
extract($displayData, EXTR_SKIP);
Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('webcomponent.field-subform');
?>
<div class="subform-repeatable-wrapper subform-layout dg-contents-cards">
 <joomla-field-subform class="subform-repeatable row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3" name="<?php echo $this->escape($name); ?>" button-add=".group-add" button-remove=".group-remove" button-move="<?php echo !empty($buttons['move']) ? '.group-move' : ''; ?>" repeatable-element=".subform-repeatable-group" minimum="<?php echo (int) $min; ?>" maximum="<?php echo (int) $max; ?>">
  <?php if (!empty($buttons['add'])): ?><div class="btn-toolbar dg-cards-add"><button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>"><span class="icon-plus" aria-hidden="true"></span></button></div><?php endif; ?>
  <?php foreach ($forms as $key => $form): echo $this->sublayout('card', ['form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $key, 'buttons' => $buttons]); endforeach; ?>
  <template class="subform-repeatable-template-section hidden"><?php echo $this->sublayout('card', ['form' => $tmpl, 'basegroup' => $fieldname, 'group' => $fieldname . 'X', 'buttons' => $buttons]); ?></template>
 </joomla-field-subform>
</div>
