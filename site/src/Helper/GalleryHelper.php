<?php
namespace Dharma\Component\Gallery\Site\Helper;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
final class GalleryHelper
{
 public static function db() { return Factory::getContainer()->get(DatabaseInterface::class); }
 public static function levels(): string { return implode(',',array_map('intval',Factory::getApplication()->getIdentity()->getAuthorisedViewLevels())); }
 public static function language(string $column): string { return Multilanguage::isEnabled() ? ' AND '.$column.' IN ('.self::db()->quote(Factory::getApplication()->getLanguage()->getTag()).", '*')" : ''; }
 public static function albums(): array {
  static $rows=null;
  return $rows ??= self::db()->setQuery('SELECT id,parent_id,title,alias,published,access,language FROM #__dharmagallery_albums')->loadObjectList('id');
 }
 public static function visibleAlbum(int $id): bool {
  $rows=self::albums(); $seen=[]; $levels=explode(',',self::levels());
  while ($id>0) {
   if (isset($seen[$id]) || !isset($rows[$id])) { return false; } $seen[$id]=true; $row=$rows[$id];
   if ((int)$row->published!==1 || !in_array((string)$row->access,$levels,true) || (Multilanguage::isEnabled() && !in_array($row->language,['*',Factory::getApplication()->getLanguage()->getTag()],true))) { return false; }
   $id=(int)$row->parent_id;
  }
  return true;
 }
 public static function layout(string $name,array $data): string { return LayoutHelper::render($name,$data,null,['component'=>'com_dharmagallery']); }

 /** Пункты меню именно галереи, доступные текущему посетителю. */
 private static function menuItems(): array
 {
     $app = Factory::getApplication();
     $levels = $app->getIdentity()->getAuthorisedViewLevels();
     return array_values(array_filter($app->getMenu()->getMenu(), static function ($item) use ($levels, $app): bool {
         if (($item->query['option'] ?? '') !== 'com_dharmagallery' || !in_array((int) $item->access, $levels, true)) { return false; }
         return !Multilanguage::isEnabled() || in_array($item->language, ['*', $app->getLanguage()->getTag()], true);
     }));
 }
 public static function menuAlbumId(object $item): int
 {
     return (int) ($item->query['id'] ?? $item->getParams()->get('album_id', 0));
 }
 public static function menuParentId(object $item): int
 {
     return (int) ($item->query['parent_id'] ?? $item->getParams()->get('parent_id', 0));
 }
 public static function route(string $view, int $id = 0): string
 {
     $activeId = (int) (Factory::getApplication()->getMenu()->getActive()->id ?? 0);
     $menus = self::menuItems();
     usort($menus, static fn($a, $b) => ((int) $b->id === $activeId) <=> ((int) $a->id === $activeId));
     foreach ($menus as $item) {
         if (($item->query['view'] ?? '') === $view &&
             (($view === 'album' && self::menuAlbumId($item) === $id) || ($view === 'albums' && self::menuParentId($item) === 0))) {
             return Route::_('index.php?Itemid=' . (int) $item->id);
         }
     }
     $itemid = 0;
     if ($view === 'album' && $id > 0) {
         $parents = self::albums(); $parent = (int) ($parents[$id]->parent_id ?? 0); $seen = [];
         while (!isset($seen[$parent])) {
             $seen[$parent] = true;
             foreach ($menus as $item) {
                 if (($item->query['view'] ?? '') === 'albums' && self::menuParentId($item) === $parent) { $itemid = (int) $item->id; break 2; }
             }
             if ($parent === 0 || !isset($parents[$parent])) { break; }
             $parent = (int) $parents[$parent]->parent_id;
         }
     }
     // Itemid=0 не даёт Joomla подставлять случайный активный список для другой ветки.
     return Route::_('index.php?option=com_dharmagallery&view=' . $view . ($id ? '&id=' . $id : '') . '&Itemid=' . $itemid);
 }
 public static function backRoute(object $album): string
 {
     $parent = (int) $album->parent_id;
     $activeId = (int) (Factory::getApplication()->getMenu()->getActive()->id ?? 0);
     $menus = self::menuItems();
     usort($menus, static fn($a, $b) => ((int) $b->id === $activeId) <=> ((int) $a->id === $activeId));
     foreach ($menus as $item) {
         if (($item->query['view'] ?? '') === 'albums' && self::menuParentId($item) === $parent) {
             return Route::_('index.php?Itemid=' . (int) $item->id);
         }
     }
     return self::route($parent ? 'album' : 'albums', $parent);
 }
 public static function columns($params): int { $v=(int)($params['columns']??0); return in_array($v,[2,3,4,6],true)?$v:(int)ComponentHelper::getParams('com_dharmagallery')->get('columns',3); }


 /** Выбранная обложка либо первое опубликованное и доступное фото самого альбома. */
 public static function cover(object $album): ?array
 {
     if (!self::visibleAlbum((int) $album->id)) { return null; }
     $explicit = trim((string) ($album->cover ?? ''));
     if ($explicit !== '') {
         try {
             $absolute = MediaHelper::absolute($explicit);
             $size = @getimagesize($absolute);
             if ($size) { return ['thumb' => MediaHelper::url($explicit), 'width' => $size[0], 'height' => $size[1]]; }
         } catch (\Throwable $e) {}
     }
     // Модель списка получает первое фото тем же запросом, без отдельного запроса для каждой карточки.
     if (property_exists($album, 'first_photo_id')) {
         if (!(int) $album->first_photo_id) { return null; }
         $photo = (object) [
             'path' => $album->first_photo_path,
             'album_id' => (int) $album->id,
             'filename' => $album->first_photo_filename,
             'type' => 'image',
         ];
     } else {
         // Поддержка вызова переопределяемого макета без модели списка.
         $db = self::db();
         $query = $db->getQuery(true)->select('*')->from('#__dharmagallery_photos')
             ->where('album_id=' . (int) $album->id)->where("type='image'")
             ->where('published=1')->where('access IN (' . self::levels() . ')')
             ->order('ordering ASC')->order('id ASC');
         $photo = $db->setQuery($query, 0, 1)->loadObject();
     }
     return $photo ? self::media($photo) : null;
 }
 public static function media(object $item, bool $optimize = true): ?array
 {
     try {
         $path = $item->path ?: 'images/dharmagallery/' . (int) $item->album_id . '/' . $item->filename;
         $real = MediaHelper::absolute($path);
         $meta = ['url' => MediaHelper::url($path), 'thumb' => MediaHelper::url($path), 'width' => 0, 'height' => 0];
         if ($item->type !== 'image' || !($size = @getimagesize($real))) { return $meta; }
         $meta['width'] = $size[0]; $meta['height'] = $size[1];
         if (!$optimize || $size[0] * $size[1] > 40000000 || !extension_loaded('gd')) { return $meta; }
         try {
             $params = ComponentHelper::getParams('com_dharmagallery');
             $webp = (bool) $params->get('convert_webp', 1) && function_exists('imagewebp');
             $quality = max(30, min(100, (int) $params->get('image_quality', 82)));
             $width = max(320, min(4096, (int) $params->get('max_image_width', 1920)));
             $height = max(320, min(4096, (int) $params->get('max_image_height', 1920)));
             $tw = max(160, min(2048, (int) $params->get('thumb_width', 640)));
             $th = max(120, min(2048, (int) $params->get('thumb_height', 480)));
             $ext = $webp ? 'webp' : ($size[2] === IMAGETYPE_PNG ? 'png' : 'jpg');
             $format = $webp ? IMAGETYPE_WEBP : ($ext === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG);
             $key = sha1($path) . '-' . sha1($path . '|' . filemtime($real) . '|' . filesize($real) . '|' . json_encode([$width, $height, $tw, $th, $quality, $ext]));
             $dir = JPATH_ROOT . '/images/dharmagallery-cache';
             if (!is_dir($dir)) { Folder::create($dir); }
             // Анимированный GIF открывается в оригинале, чтобы не терять анимацию.
             if ($size[2] !== IMAGETYPE_GIF && ($webp || $size[0] > $width || $size[1] > $height)) {
                 $full = 'images/dharmagallery-cache/' . $key . '-full.' . $ext;
                 self::derivative($real, JPATH_ROOT . '/' . $full, min($width, $size[0]), min($height, $size[1]), $format, $quality);
                 $meta['url'] = MediaHelper::url($full);
             }
             $thumb = 'images/dharmagallery-cache/' . $key . '-thumb.' . $ext;
             self::derivative($real, JPATH_ROOT . '/' . $thumb, min($tw, $size[0]), min($th, $size[1]), $format, $quality);
             $meta['thumb'] = MediaHelper::url($thumb);
             [$meta['width'], $meta['height']] = getimagesize(JPATH_ROOT . '/' . $thumb);
         } catch (\Throwable $e) {
             // При недоступности GD/кэша остаётся рабочая ссылка на оригинал.
         }
         return $meta;
     } catch (\Throwable $e) { return null; }
 }
 private static function derivative(string $source, string $destination, int $width, int $height, int $format, int $quality): void
 {
     if (is_file($destination)) { return; }
     $temporary = $destination . '.' . bin2hex(random_bytes(4)) . '.tmp';
     try {
         $image = new Image($source);
         $image->resize($width, $height, false, Image::SCALE_INSIDE);
         $image->toFile($temporary, $format, ['quality' => $format === IMAGETYPE_PNG ? 6 : $quality]);
         if (!rename($temporary, $destination)) { throw new \RuntimeException('Cannot write image cache'); }
     } finally {
         if (is_file($temporary)) { unlink($temporary); }
     }
 }
 public static function assets($doc,bool $lightbox=false): void {
  $wa=$doc->getWebAssetManager(); $wa->getRegistry()->addExtensionRegistryFile('com_dharmagallery');
  if ($lightbox) { \Joomla\CMS\HTML\HTMLHelper::_('bootstrap.modal','.dg-lightbox'); $wa->useScript('com_dharmagallery.lightbox'); }
 }
}
