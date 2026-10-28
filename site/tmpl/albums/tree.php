<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
?>
<div class="com-dharmagallery dg-filetree mt-4">
 <h1 class="mb-4"><?php echo $this->escape($this->pageHeading); ?></h1>
 <?php if (!empty($this->parent->introtext)): ?><div class="dg-album-intro mb-4"><?php echo HTMLHelper::_('content.prepare', $this->parent->introtext, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
 <?php if (!$this->filetree['files'] && !$this->filetree['children']): ?><p><?php echo Text::_('COM_DHARMAGALLERY_EMPTY'); ?></p><?php else: ?>
  <?php if ($this->filetree['files']): ?><ul class="list-unstyled mb-3"><?php foreach ($this->filetree['files'] as $file): ?>
   <li class="mb-1"><?php echo H::layout('tree-file', ['item' => $file, 'params' => $this->filetree['params']]); ?></li>
  <?php endforeach; ?></ul><?php endif; ?>
  <?php if ($this->filetree['children']): ?><ul class="list-unstyled mb-0"><?php foreach ($this->filetree['children'] as $node): ?>
   <li class="mb-4"><?php echo H::layout('tree-node', ['node' => $node, 'depth' => 0]); ?></li>
  <?php endforeach; ?></ul><?php endif; ?>
 <?php endif; ?>
 <?php if (!empty($this->parent->description)): ?><div class="dg-album-fulltext mt-4"><?php echo HTMLHelper::_('content.prepare', $this->parent->description, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
</div>
