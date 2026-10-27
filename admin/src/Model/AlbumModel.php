<?php
namespace Dharma\Component\Gallery\Administrator\Model;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Dharma\Component\Gallery\Administrator\Service\AlbumContentsService;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
class AlbumModel extends RecordModel
{
    public $typeAlias = 'com_dharmagallery.album';

    public function getTable($type = 'Album', $prefix = 'Administrator', $config = [])
    {
        return parent::getTable($type, $prefix, $config);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);
        if ($item) {
            if (is_string($item->params)) { $item->params = json_decode($item->params, true) ?: []; }
            if (!(int) $item->id) {
                $item->mode = 'gallery';
                $item->parent_id = (int) Factory::getApplication()->getUserState('com_dharmagallery.new_parent_id', 0);
            }
            $item->mediaitems = array_values((new AlbumContentsService($this->getDatabase()))->load((int) $item->id));
        }
        return $item;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = parent::getForm($data, $loadData);
        if (!$form) { return false; }
        $item = $this->getItem();
        // Сохранённый тип берётся из БД, а не из запроса или пользовательского состояния формы.
        if (!empty($item->id)) {
            // Для закреплённого типа используем штатный readonly-список:
            // он показывает выбранное значение без инициализации Choices.
            $form->setFieldAttribute('mode', 'layout', 'joomla.form.field.list');
            $form->setFieldAttribute('mode', 'readonly', 'true');
            $form->setFieldAttribute('mode', 'disabled', 'false');
            $form->setValue('mode', null, $item->mode);
        }
        $mode = !empty($item->id) ? $item->mode : (is_array($data) ? ($data['mode'] ?? 'gallery') : 'gallery');
        if (!empty($item->id)) {
            $wrong = $mode === 'gallery' ? ['files_layout', 'show_size', 'show_extension', 'download'] : ['columns', 'image_mode', 'show_title', 'lightbox'];
            foreach ($wrong as $name) { $form->removeField($name, 'params'); }
        }
        $form->setFieldAttribute('mediaitems', 'album_mode', $mode);
        return $form;
    }

    public function getChildren(int $albumId): array
    {
        return $this->getDatabase()->setQuery('SELECT id, title, mode, published FROM #__dharmagallery_albums WHERE parent_id=' . $albumId . ' ORDER BY ordering, id')->loadObjectList();
    }

    protected function getReorderConditions($table) { return ['parent_id=' . (int) $table->parent_id]; }

    public function save($data)
    {
        $db = $this->getDatabase();
        try {
            if ((int) ($data['mediaitems_complete'] ?? 0) !== 1) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INCOMPLETE_FORM'); }
            $id = (int) ($data['id'] ?? 0);
            $old = $id ? $db->setQuery('SELECT id, mode FROM #__dharmagallery_albums WHERE id=' . $id)->loadObject() : null;
            if ($id && !$old) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_ALBUM'); }
            $data['mode'] = $old ? $old->mode : ($data['mode'] ?? 'gallery');
            if (!in_array($data['mode'], ['gallery', 'files'], true)) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_MODE'); }
            if (!empty($data['cover'])) {
                $data['cover'] = MediaHelper::path($data['cover']);
                if (MediaHelper::metadata($data['cover'])['type'] !== 'image') { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_IMAGE'); }
            }
            $allowed = $data['mode'] === 'gallery' ? ['columns', 'image_mode', 'show_title', 'lightbox'] : ['files_layout', 'show_size', 'show_extension', 'download'];
            $data['params'] = array_intersect_key((array) ($data['params'] ?? []), array_flip($allowed));
            $service = new AlbumContentsService($db);
            $prepared = $service->prepare((array) ($data['mediaitems'] ?? []), $id, $data['mode']);
            unset($data['mediaitems'], $data['mediaitems_complete']);
            $db->transactionStart();
            if (!parent::save($data)) { $db->transactionRollback(); return false; }
            $service->store($prepared, (int) $this->getState('album.id'));
            $db->transactionCommit();
            return true;
        } catch (\Throwable $e) {
            if (isset($prepared)) { try { $db->transactionRollback(); } catch (\Throwable $ignored) {} }
            $this->setError(Text::_($e->getMessage()));
            return false;
        }
    }

    public function delete(&$pks)
    {
        foreach ($pks as $id) {
            $db = $this->getDatabase();
            if ($db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_albums WHERE parent_id=' . (int) $id)->loadResult() || $db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_photos WHERE album_id=' . (int) $id)->loadResult()) {
                $this->setError(Text::_('COM_DHARMAGALLERY_ALBUM_NOT_EMPTY')); return false;
            }
        }
        return parent::delete($pks);
    }
}
