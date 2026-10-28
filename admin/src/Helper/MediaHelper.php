<?php
namespace Dharma\Component\Gallery\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
final class MediaHelper
{
 public const TYPES = [
  'jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'], 'webp'=>['image/webp'], 'gif'=>['image/gif'],
  'pdf'=>['application/pdf'], 'txt'=>['text/plain'], 'csv'=>['text/plain','text/csv','application/csv'],
  'rtf'=>['application/rtf','text/rtf','application/x-rtf','text/plain'],
  'doc'=>['application/msword','application/x-ole-storage','application/CDFV2','text/rtf','text/plain'],
  'xls'=>['application/vnd.ms-excel','application/x-ole-storage','application/CDFV2'],
  'ppt'=>['application/vnd.ms-powerpoint','application/x-ole-storage','application/CDFV2'],
  'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'],
  'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'],
  'pptx'=>['application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip'],
  'odt'=>['application/vnd.oasis.opendocument.text','application/zip'],
  'ods'=>['application/vnd.oasis.opendocument.spreadsheet','application/zip'],
  'odp'=>['application/vnd.oasis.opendocument.presentation','application/zip'],
 ];

 public static function parseExtensions(string $list): array
 {
     $extensions = [];
     foreach (preg_split('/[\s,;]+/u', strtolower(trim($list)), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $value) {
         $extension = ltrim($value, '.');
         if (!isset(self::TYPES[$extension])) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_EXTENSIONS'); }
         $extensions[$extension] = $extension;
     }
     return array_values($extensions);
 }
 public static function allowedExtensions(string $mode = 'files'): array
 {
     $value = (string) \Joomla\CMS\Component\ComponentHelper::getParams('com_dharmagallery')->get('allowed_file_extensions', implode(',', array_keys(self::TYPES)));
     $extensions = self::parseExtensions($value);
     return $mode === 'gallery' ? array_values(array_intersect($extensions, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) : $extensions;
 }
 public static function assertAllowed(string $path, string $mode): void
 {
     if (!in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::allowedExtensions($mode), true)) {
         throw new \InvalidArgumentException('COM_DHARMAGALLERY_EXTENSION_NOT_ALLOWED');
     }
 }
 /** Читаемое имя: кириллица и пробелы сохраняются, служебные символы нормализуются. */
 public static function uploadName(string $original): array
 {
     if (!mb_check_encoding($original, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $original)) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_FILENAME'); }
     $original = basename(str_replace('\\', '/', $original));
     if (class_exists(\Normalizer::class)) { $original = \Normalizer::normalize($original, \Normalizer::FORM_C) ?: $original; }
     $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
     if (!isset(self::TYPES[$extension])) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
     $stem = preg_replace('/\p{Z}/u', ' ', pathinfo($original, PATHINFO_FILENAME));
     $stem = preg_replace('/[^\p{L}\p{M}\p{N} №_.,()+@-]/u', '_', $stem);
     $stem = trim(mb_strcut($stem, 0, 180, 'UTF-8'), ' .');
     return [$stem !== '' ? $stem : 'Файл', $extension];
 }
 public static function path(string $path): string
 {
  $path = explode('#', trim($path), 2)[0];
  $path = preg_replace('#^local-images:/+#', 'images/', $path);
  if (!preg_match('#^images/[\p{L}\p{M}\p{N} №_.,()+@/\-]+$#u', $path) || strlen($path)>1024) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_PATH'); }
  foreach (explode('/', $path) as $part) { if ($part==='' || $part==='.' || $part==='..' || $part[0]==='.') { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_PATH'); } }
  if (!isset(self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))])) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  return $path;
 }
 public static function absolute(string $path): string
 {
  $path=self::path($path); $root=realpath(JPATH_ROOT.'/images'); $real=realpath(JPATH_ROOT.'/'.$path);
  if (!$root || !$real || !is_file($real) || !str_starts_with($real,$root.DIRECTORY_SEPARATOR)) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_MISSING_FILE'); }
  return $real;
 }
 public static function metadata(string $path): array
 {
  $real=self::absolute($path); $ext=strtolower(pathinfo($path,PATHINFO_EXTENSION)); $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($real);
  if (!in_array($mime,self::TYPES[$ext],true)) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  $rtfMime = in_array($mime, ['text/rtf','text/plain','application/rtf','application/x-rtf'], true);
  $rtfSignature = str_starts_with(ltrim((string) file_get_contents($real, false, null, 0, 64)), "{\\rtf");
  // Сигнатура RTF обязательна для .rtf и для .doc, которые определились как RTF/текст; обычные .txt и .csv — тоже text/plain и её не имеют.
  if (($ext === 'rtf' || ($ext === 'doc' && $rtfMime)) && !$rtfSignature) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  if (in_array($ext,['docx','xlsx','pptx','odt','ods','odp'],true)) {
   $zip=new \ZipArchive(); if ($zip->open($real)!==true) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
   $entry=match($ext) {'docx'=>'word/document.xml','xlsx'=>'xl/workbook.xml','pptx'=>'ppt/presentation.xml',default=>'mimetype'};
   $valid=$zip->locateName($entry)!==false;
   if (in_array($ext,['odt','ods','odp'],true)) { $valid=$valid && $zip->getFromName('mimetype')===self::TYPES[$ext][0]; }
   $zip->close(); if (!$valid) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  }
  $image=str_starts_with($mime,'image/');
  if ($image) { $size=@getimagesize($real); if (!$size || $size[0]*$size[1]>40000000) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_IMAGE'); } }
  return ['type'=>$image?'image':'file','mime'=>$mime,'size'=>filesize($real)];
 }
 public static function url(string $path): string { return Uri::root(true).'/'.implode('/',array_map('rawurlencode',explode('/',self::path($path)))); }
 public static function size(int $bytes): string { return $bytes<1048576 ? round($bytes/1024,1).' KB' : round($bytes/1048576,1).' MB'; }
 public static function asset(int $id): string { return $id>0 ? 'com_dharmagallery.album.'.$id : 'com_dharmagallery'; }

 public static function canItem($user, string $action, int $albumId): bool
 {
     return $albumId > 0 && $user->authorise($action, self::asset($albumId));
 }
}
