<?php
namespace Dharma\Component\Gallery\Administrator\Service;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/** Содержимое сохраняется вместе с альбомом и внутри его транзакции. */
final class AlbumContentsService
{
    public function __construct(private DatabaseInterface $db) {}

    public function load(int $albumId): array
    {
        if ($albumId <= 0) { return []; }
        return $this->db->setQuery('SELECT * FROM #__dharmagallery_photos WHERE album_id=' . $albumId . ' ORDER BY ordering, id')->loadObjectList('id');
    }

    public function prepare(array $rows, int $albumId, string $mode): array
    {
        $original = $this->load($albumId);
        $user = Factory::getApplication()->getIdentity();
        $canCreate = $user->authorise('core.create', MediaHelper::asset($albumId));
        $canState = $user->authorise('core.edit.state', MediaHelper::asset($albumId));
        $limit = max(1, min(300, (int) ComponentHelper::getParams('com_dharmagallery')->get('max_items', 100)));
        if (count($rows) > max($limit, count($original))) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_TOO_MANY_FILES'); }
        $result = []; $seen = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $id = (int) ($row['id'] ?? 0);
            if ($id && (!isset($original[$id]) || isset($seen[$id]))) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_ITEM'); }
            $path = trim((string) ($row['path'] ?? ''));
            if ($path === '' && !$id) { continue; }
            if (!$id && !$canCreate) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR'); }
            $path = MediaHelper::path($path);
            $meta = MediaHelper::metadata($path);
            if ($mode === 'gallery' && $meta['type'] !== 'image') { throw new \InvalidArgumentException('COM_DHARMAGALLERY_IMAGES_ONLY'); }
            if ($id) { $seen[$id] = true; }
            $published = $canState ? (int) ($row['published'] ?? 1) : (int) ($original[$id]->published ?? 1);
            $result[] = array_merge($meta, [
                'id' => $id,
                'album_id' => $albumId,
                'path' => $path,
                'filename' => basename($path),
                'title' => mb_substr(trim(strip_tags((string) ($row['title'] ?? ''))) ?: pathinfo($path, PATHINFO_FILENAME), 0, 255),
                'alt' => $mode === 'gallery' ? mb_substr(trim(strip_tags((string) ($row['alt'] ?? ''))), 0, 255) : '',
                'description' => mb_substr(trim(strip_tags((string) ($row['description'] ?? ''))), 0, 10000),
                'published' => in_array($published, [0, 1], true) ? $published : 0,
                'access' => (int) ($original[$id]->access ?? 1),
                'ordering' => count($result) + 1,
            ]);
        }
        $removed = array_diff(array_keys($original), array_keys($seen));
        if ($removed && !$user->authorise('core.delete', MediaHelper::asset($albumId))) { throw new \RuntimeException('JERROR_ALERTNOAUTHOR'); }
        return ['items' => $result, 'removed' => $removed];
    }

    public function store(array $prepared, int $albumId): void
    {
        $user = Factory::getApplication()->getIdentity();
        $now = Factory::getDate()->toSql();
        foreach ($prepared['items'] as $row) {
            $row['album_id'] = $albumId;
            $record = (object) $row;
            $record->modified = $now;
            $record->modified_by = (int) $user->id;
            if ($record->id) {
                $this->db->updateObject('#__dharmagallery_photos', $record, 'id');
            } else {
                unset($record->id);
                $record->created = $now;
                $record->created_by = (int) $user->id;
                $this->db->insertObject('#__dharmagallery_photos', $record, 'id');
            }
        }
        if ($prepared['removed']) {
            // Удаляется только связь с альбомом; оригинал может использоваться в других материалах.
            $this->db->setQuery('DELETE FROM #__dharmagallery_photos WHERE album_id=' . $albumId . ' AND id IN (' . implode(',', array_map('intval', $prepared['removed'])) . ')')->execute();
        }
    }
}
