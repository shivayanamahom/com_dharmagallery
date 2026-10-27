<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper as M;
use Joomla\CMS\Language\Text;
$item = $displayData['item']; $params = $displayData['params'];
$media = H::media($item, false); if (!$media) { return; }
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$download = (int) ($params['download'] ?? 1) === 1;
?>
<article class="card h-100"><div class="card-body">
 <?php if ((int) ($params['show_extension'] ?? 1) === 1): ?><span class="badge bg-secondary mb-2"><?php echo $e(strtoupper(pathinfo($item->path, PATHINFO_EXTENSION))); ?></span><?php endif; ?>
 <h2 class="h5"><?php echo $e($item->title); ?></h2>
 <?php if ($item->description): ?><p><?php echo nl2br($e($item->description)); ?></p><?php endif; ?>
 <?php if ((int) ($params['show_size'] ?? 1) === 1): ?><p class="small text-muted"><?php echo $e(M::size((int) $item->size)); ?></p><?php endif; ?>
 <a class="btn btn-secondary" href="<?php echo $e($media['url']); ?>"<?php echo $download ? ' download="' . $e(basename($item->path)) . '"' : ' target="_blank" rel="noopener"'; ?>><?php echo Text::_($download ? 'COM_DHARMAGALLERY_DOWNLOAD' : 'COM_DHARMAGALLERY_OPEN'); ?></a>
</div></article>
