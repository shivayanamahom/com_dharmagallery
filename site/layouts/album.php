<?php
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper as M;
$item=$displayData['item']; $e=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); $cover='';
if ($item->cover) { try { M::absolute($item->cover); $cover=M::url($item->cover); } catch (\Throwable $ex) {} }
?>
<article class="card h-100">
<?php if ($cover): ?><a href="<?php echo $e(H::route('album',(int)$item->id)); ?>" tabindex="-1" aria-hidden="true"><img src="<?php echo $e($cover); ?>" alt="" loading="lazy" decoding="async" class="card-img-top img-fluid"></a><?php endif; ?>
<div class="card-body"><h2 class="h4 card-title"><a href="<?php echo $e(H::route('album',(int)$item->id)); ?>"><?php echo $e($item->title); ?></a></h2></div></article>
