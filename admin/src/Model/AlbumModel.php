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
        return $this->getDatabase()->setQuery('SELECT id, title, mode, published FROM #__dharmagallery_albums WHERE parent_id=' . $albumId . ' AND published>=0 ORDER BY ordering, id')->loadObjectList();
    }

    protected function getReorderConditions($table) { return ['parent_id=' . (int) $table->parent_id]; }

    public function save($data)
    {
        $db = $this->getDatabase();
        try {
            if ((int) ($data['mediaitems_complete'] ?? 0) !== 1) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INCOMPLETE_FORM'); }
            $id = (int) ($data['id'] ?? 0);
            $old = $id ? $db->setQuery('SELECT id, mode, parent_id FROM #__dharmagallery_albums WHERE id=' . $id)->loadObject() : null;
            if ($id && !$old) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_ALBUM'); }
            $data['mode'] = $old ? $old->mode : ($data['mode'] ?? 'gallery');
            $parent = (int) ($data['parent_id'] ?? ($old->parent_id ?? 0));
            if ($parent && $parent !== (int) ($old->parent_id ?? 0)) {
                $seen = [];
                while ($parent > 0) {
                    $row = $db->setQuery('SELECT parent_id,published FROM #__dharmagallery_albums WHERE id=' . $parent)->loadObject();
                    if (!$row || (int) $row->published !== 1 || isset($seen[$parent])) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_PARENT_UNAVAILABLE'); }
                    $seen[$parent] = true; $parent = (int) $row->parent_id;
                }
            }
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
            $service->cleanup($prepared);
            return true;
        } catch (\Throwable $e) {
            if (isset($prepared)) { try { $db->transactionRollback(); } catch (\Throwable $ignored) {} }
            $this->setError(Text::_($e->getMessage()));
            return false;
        }
    }


    public function delete(&$pks)
    {
        $db = $this->getDatabase();
        $pks = array_values(array_unique(array_filter(array_map('intval', (array) $pks))));
        if (!$pks) { return false; }
        $ids = implode(',', $pks);
        if ($db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_albums WHERE parent_id IN (' . $ids . ') AND id NOT IN (' . $ids . ')')->loadResult()) {
            $this->setError(Text::_('COM_DHARMAGALLERY_REMOVE_CHILDREN_FIRST')); return false;
        }
        $table = $this->getTable();
        foreach ($pks as $id) {
            if (!$table->load($id) || !$this->canDelete($table)) { $this->setError(Text::_('JERROR_ALERTNOAUTHOR')); return false; }
        }
        $parents = $db->setQuery('SELECT id,parent_id FROM #__dharmagallery_albums')->loadAssocList('id');
        $depth = static function (int $id) use ($parents): int {
            $seen = []; $level = 0;
            while (isset($parents[$id]) && !isset($seen[$id])) { $seen[$id] = true; $id = (int) $parents[$id]['parent_id']; $level++; }
            return $level;
        };
        usort($pks, static fn($a, $b) => $depth($b) <=> $depth($a));
        $paths = $db->setQuery('SELECT path FROM #__dharmagallery_photos WHERE album_id IN (' . $ids . ')')->loadColumn();
        $covers = $db->setQuery('SELECT cover FROM #__dharmagallery_albums WHERE id IN (' . $ids . ')')->loadColumn();
        try {
            $db->transactionStart();
            if (!parent::delete($pks)) { $db->transactionRollback(); return false; }
            $db->setQuery('DELETE FROM #__dharmagallery_photos WHERE album_id IN (' . $ids . ')')->execute();
            $db->transactionCommit();
        } catch (\Throwable $e) { $db->transactionRollback(); $this->setError($e->getMessage()); return false; }
        if ((int) \Joomla\CMS\Component\ComponentHelper::getParams('com_dharmagallery')->get('delete_album_files', 0) === 1) {
            (new \Dharma\Component\Gallery\Administrator\Service\StorageService($db))->deleteUnreferenced(array_merge($paths, $covers));
        }
        return true;
    }
}
