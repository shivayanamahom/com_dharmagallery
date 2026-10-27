<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
$item = $displayData['item'];
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$cover = H::cover($item);
?>
<article class="card h-100">
<?php if ($cover): ?><a href="<?php echo $e(H::route('album',(int)$item->id)); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo $e($cover['thumb']); ?>" alt="" width="<?php echo (int) $cover['width']; ?>" height="<?php echo (int) $cover['height']; ?>" loading="lazy" decoding="async" decoding="async" class="card-img-top img-fluid"></a><?php endif; ?>
<div class="card-body"><h2 class="h4 card-title"><a href="<?php echo $e(H::route('album',(int)$item->id)); ?>"><?php echo $e($item->title); ?></a></h2></div></article>
