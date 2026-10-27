<?php
namespace Dharma\Component\Gallery\Administrator\Helper;
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
final class MediaHelper
{
 public const TYPES = [
  'jpg'=>['image/jpeg'], 'jpeg'=>['image/jpeg'], 'png'=>['image/png'], 'webp'=>['image/webp'], 'gif'=>['image/gif'],
  'pdf'=>['application/pdf'], 'txt'=>['text/plain'], 'csv'=>['text/plain','text/csv','application/csv'],
  'doc'=>['application/msword','application/x-ole-storage','application/CDFV2'],
  'xls'=>['application/vnd.ms-excel','application/x-ole-storage','application/CDFV2'],
  'ppt'=>['application/vnd.ms-powerpoint','application/x-ole-storage','application/CDFV2'],
  'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'],
  'xlsx'=>['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/zip'],
  'pptx'=>['application/vnd.openxmlformats-officedocument.presentationml.presentation','application/zip'],
  'odt'=>['application/vnd.oasis.opendocument.text','application/zip'],
  'ods'=>['application/vnd.oasis.opendocument.spreadsheet','application/zip'],
  'odp'=>['application/vnd.oasis.opendocument.presentation','application/zip'],
 ];
 public static function path(string $path): string
 {
  $path = explode('#', trim($path), 2)[0];
  $path = preg_replace('#^local-images:/+#', 'images/', $path);
  if (!preg_match('#^images/[\p{L}\p{N} _.,()+@/\-]+$#u', $path) || strlen($path)>1024) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_PATH'); }
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
