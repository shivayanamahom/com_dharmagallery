<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Joomla\CMS\Language\Text;
$items = $displayData['items'];
$params = $displayData['params'];
$files = ($params['mode'] ?? 'gallery') === 'files';
$columns = $files ? (($params['files_layout'] ?? 'list') === 'grid' ? 3 : 1) : H::columns($params);
if (!in_array($columns, [1, 2, 3, 4, 6], true)) { $columns = 3; }
?>
<?php if (!$items): ?><p><?php echo Text::_('COM_DHARMAGALLERY_EMPTY'); ?></p><?php else: ?>
<div class="row row-cols-1 <?php echo $columns > 1 ? 'row-cols-sm-2 row-cols-lg-' . $columns : ''; ?> g-4 dg-items">
 <?php foreach ($items as $item): ?><div class="col"><?php echo H::layout($files ? 'file' : 'image', ['item' => $item, 'params' => $params]); ?></div><?php endforeach; ?>
</div>
<?php if (!$files && (int) ($params['lightbox'] ?? 1) === 1): echo H::layout('lightbox', []); endif; ?>
<?php endif; ?>
