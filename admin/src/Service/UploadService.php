<?php
namespace Dharma\Component\Gallery\Administrator\Service;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Helper\MediaHelper as JoomlaMediaHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
final class UploadService
{
 public function upload(array $file,int $albumId,string $mode='gallery'): array
 {
  if ((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
  $limit=max(1,min(100,(int)ComponentHelper::getParams('com_dharmagallery')->get('upload_maxsize',20)))*1048576;
  if (filesize($file['tmp_name'])>$limit) { throw new \RuntimeException('COM_DHARMAGALLERY_FILE_TOO_LARGE'); }
  $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION)); if (!isset(MediaHelper::TYPES[$ext])) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  $name=OutputFilter::stringURLSafe(pathinfo($file['name'],PATHINFO_FILENAME));
  $name=substr($name,0,100) ?: 'file'; $name.='-'.bin2hex(random_bytes(8)).'.'.$ext;
  $directory = StorageService::albumDirectory($albumId);
  $relative = $directory . '/' . $name;
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
  if ($mode === 'gallery' && !str_starts_with($mime, 'image/')) { throw new \RuntimeException('COM_DHARMAGALLERY_IMAGES_ONLY'); }
  if (!in_array($mime,MediaHelper::TYPES[$ext],true)) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  // Native Joomla executable-extension policy supplements the fixed allowlist and content checks.
  ComponentHelper::getParams('com_dharmagallery')->set('restrict_uploads_extensions',implode(',',array_keys(MediaHelper::TYPES)));
  if (!JoomlaMediaHelper::checkFileExtension($ext,'com_dharmagallery')) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_TYPE'); }
  $dest=JPATH_ROOT.'/'.$relative;
  if (!File::upload($file['tmp_name'],$dest,false,false)) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
  try { $meta=MediaHelper::metadata($relative); (new StorageService(Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class)))->register($relative, $albumId); } catch (\Throwable $e) { File::delete($dest); throw $e; }
  return array_merge(['path'=>$relative,'title'=>pathinfo($file['name'],PATHINFO_FILENAME)],$meta);
 }
}
