<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<div class="com-dharmagallery"><h1><?php echo $this->escape($this->pageHeading); ?></h1>
<?php if (!empty($this->parent->introtext)): ?><div class="dg-album-intro mb-4"><?php echo HTMLHelper::_('content.prepare', $this->parent->introtext, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
<?php if (!$this->items): ?><p><?php echo Text::_('COM_DHARMAGALLERY_EMPTY'); ?></p><?php else: ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4"><?php foreach ($this->items as $item): ?><div class="col"><?php echo H::layout('album',['item'=>$item]); ?></div><?php endforeach; ?></div>
<?php echo $this->pagination->getPagesLinks(); endif; ?>
<?php if (!empty($this->parent->description)): ?><div class="dg-album-fulltext mt-4"><?php echo HTMLHelper::_('content.prepare', $this->parent->description, '', 'com_dharmagallery.album'); ?></div><?php endif; ?></div>
