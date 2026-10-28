<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<div class="com-dharmagallery mt-4"><h1 class="mb-4"><?php echo $this->escape($this->pageHeading); ?></h1>
<?php if (!empty($this->album->introtext)): ?><div class="dg-album-intro mb-4"><?php echo HTMLHelper::_('content.prepare', $this->album->introtext, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
<?php if ($this->children): ?><div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 mb-4"><?php foreach ($this->children as $item): ?><div class="col"><?php echo H::layout('album',['item'=>$item]); ?></div><?php endforeach; ?></div><?php endif; ?>
<?php echo H::layout('items',['items'=>$this->items,'params'=>$this->params]); echo $this->pagination->getPagesLinks(); ?>
<?php if ($this->album->description): ?><div class="dg-album-fulltext mt-4"><?php echo HTMLHelper::_('content.prepare', $this->album->description, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
<p class="mt-4"><a class="btn btn-secondary" href="<?php echo H::backRoute($this->album); ?>"><?php echo Text::_('COM_DHARMAGALLERY_BACK'); ?></a></p></div>
