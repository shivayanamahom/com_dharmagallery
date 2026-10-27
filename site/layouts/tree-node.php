<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\HTML\HTMLHelper;
$node = $displayData['node'];
$depth = max(0, (int) ($displayData['depth'] ?? 0));
$heading = min(6, $depth + 2);
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section aria-labelledby="dg-tree-album-<?php echo (int) $node->album->id; ?>">
 <h<?php echo $heading; ?> class="h5 mb-2" id="dg-tree-album-<?php echo (int) $node->album->id; ?>"><?php echo $e($node->album->title); ?></h<?php echo $heading; ?>>
 <?php if ($node->album->introtext): ?><div class="mb-2"><?php echo HTMLHelper::_('content.prepare', $node->album->introtext, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
 <?php if ($node->files): ?><ul class="list-unstyled ms-3 ms-sm-4 mb-3"><?php foreach ($node->files as $file): ?>
  <li class="mb-1"><?php echo H::layout('tree-file', ['item' => $file, 'params' => $node->params]); ?></li>
 <?php endforeach; ?></ul><?php endif; ?>
 <?php if ($node->children): ?><ul class="list-unstyled ms-3 ms-sm-4 mb-0"><?php foreach ($node->children as $child): ?>
  <li class="mb-3"><?php echo H::layout('tree-node', ['node' => $child, 'depth' => $depth + 1]); ?></li>
 <?php endforeach; ?></ul><?php endif; ?>
 <?php if ($node->album->description): ?><div class="mt-2"><?php echo HTMLHelper::_('content.prepare', $node->album->description, '', 'com_dharmagallery.album'); ?></div><?php endif; ?>
</section>
