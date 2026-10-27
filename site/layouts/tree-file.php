<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper as M;
$item = $displayData['item'];
$params = H::settings($displayData['params']);
$media = H::media($item, false);
if (!$media) { return; }
$e = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$download = $params['download'] === 1;
?>
<?php if ($params['show_extension']): ?><span class="badge bg-light text-dark me-1"><?php echo $e(strtoupper(pathinfo($item->path, PATHINFO_EXTENSION))); ?></span><?php endif; ?>
<a href="<?php echo $e($media['url']); ?>"<?php echo $download ? ' download="' . $e(basename($item->path)) . '"' : ' target="_blank" rel="noopener"'; ?>><?php echo $e($item->title); ?></a>
<?php if ($params['show_size']): ?><span class="small text-muted ms-1">(<?php echo $e(M::size((int) $item->size)); ?>)</span><?php endif; ?>
<?php if ($item->description): ?><span class="d-block small text-muted"><?php echo nl2br($e($item->description)); ?></span><?php endif; ?>
