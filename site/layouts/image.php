<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
$item = $displayData['item']; $params = $displayData['params'];
$media = H::media($item); if (!$media || $item->type !== 'image') { return; }
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$crop = ($params['image_mode'] ?? 'natural') === 'cover';
$showTitle = H::showPhotoTitles($params);
?>
<figure class="card h-100 mb-0">
 <a href="<?php echo $e($media['url']); ?>"<?php if ((int) ($params['lightbox'] ?? 1) === 1): ?> data-dg-image data-title="<?php echo $showTitle ? $e($item->title) : ''; ?>"<?php endif; ?> class="d-block<?php echo $crop ? ' ratio ratio-4x3' : ''; ?>">
  <img src="<?php echo $e($media['thumb']); ?>" alt="<?php echo $e($item->alt); ?>" width="<?php echo (int) $media['width']; ?>" height="<?php echo (int) $media['height']; ?>" loading="lazy" decoding="async" class="img-fluid w-100<?php echo $crop ? ' h-100 object-fit-cover' : ''; ?>">
 </a>
 <?php if ($showTitle || $item->description): ?><figcaption class="card-body">
  <?php if ($showTitle): ?><span class="d-block fw-semibold"><?php echo $e($item->title); ?></span><?php endif; ?>
  <?php if ($item->description): ?><span class="d-block mt-2"><?php echo nl2br($e($item->description)); ?></span><?php endif; ?>
 </figcaption><?php endif; ?>
</figure>
