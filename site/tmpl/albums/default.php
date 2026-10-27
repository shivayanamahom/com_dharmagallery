<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\Language\Text;
?>
<div class="com-dharmagallery"><h1><?php echo $this->escape($this->pageHeading); ?></h1>
<?php if (!$this->items): ?><p><?php echo Text::_('COM_DHARMAGALLERY_EMPTY'); ?></p><?php else: ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4"><?php foreach ($this->items as $item): ?><div class="col"><?php echo H::layout('album',['item'=>$item]); ?></div><?php endforeach; ?></div>
<?php echo $this->pagination->getPagesLinks(); endif; ?></div>
