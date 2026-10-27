<?php
namespace Dharma\Component\Gallery\Administrator\Controller;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
class AlbumController extends FormController
{
 protected $option='com_dharmagallery';
 protected function allowAdd($data=[]) { return $this->app->getIdentity()->authorise('core.create',MediaHelper::asset(0)); }
 protected function allowEdit($data=[],$key='id') {
  $item=$this->getModel()->getItem((int)($data[$key]??0));
  return $item && $this->app->getIdentity()->authorise('core.edit',MediaHelper::asset((int)$item->id));
 }

 public function add()
 {
     $this->app->setUserState('com_dharmagallery.new_parent_id', $this->input->getInt('parent_id', 0));
     return parent::add();
 }
 public function upload()
 {
     try {
         if (!$this->checkToken('post', false)) { throw new \RuntimeException('JINVALID_TOKEN', 403); }
         $albumId = $this->input->post->getInt('album_id');
         $user = $this->app->getIdentity();
         if (!MediaHelper::canItem($user, 'core.create', $albumId) || !MediaHelper::canItem($user, 'core.edit', $albumId)) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403); }
         $table = $this->getModel()->getTable();
         if (!$table->load($albumId) || (int) $table->published === -2) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_ALBUM', 400); }
         if ($table->checked_out && (int) $table->checked_out !== (int) $user->id) { throw new \RuntimeException('JLIB_APPLICATION_ERROR_CHECKOUT_USER_MISMATCH', 403); }
         $file = $this->input->files->get('file', [], 'array');
         $result = (new \Dharma\Component\Gallery\Administrator\Service\UploadService())->upload($file, $albumId, $table->mode);
         // Файл сохраняется в медиапапку. Строка и подписи попадут в БД при сохранении альбома.
         echo new \Joomla\CMS\Response\JsonResponse($result);
     } catch (\Throwable $e) {
         $code = $e->getCode() === 403 ? 403 : 400;
         $this->app->setHeader('status', $code . ' Bad Request', true);
         echo new \Joomla\CMS\Response\JsonResponse(null, \Joomla\CMS\Language\Text::_($e->getMessage()), true);
     }
     $this->app->close();
 }
}
