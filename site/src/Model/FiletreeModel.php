<?php
namespace Dharma\Component\Gallery\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;

/** Полная доступная ветка файлов без пагинации и запросов для каждого альбома. */
class FiletreeModel extends BaseDatabaseModel
{
    protected $option = 'com_dharmagallery';

    public function getTree(int $parentId = 0): array
    {
        if ($parentId && !H::visibleAlbum($parentId)) {
            throw new \RuntimeException(Text::_('COM_DHARMAGALLERY_NOT_FOUND'), 404);
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select('a.*')->from('#__dharmagallery_albums AS a')
            ->where('a.published=1')->where('a.access IN (' . H::levels() . ')' . H::language('a.language'))
            ->order('a.ordering ASC')->order('a.id ASC');
        $albums = $db->setQuery($query)->loadObjectList('id');
        $children = [];
        foreach ($albums as $album) { $children[(int) $album->parent_id][] = (int) $album->id; }
        $nodes = []; $visited = [];
        $build = function (int $id) use (&$build, &$nodes, &$visited, $albums, $children): ?object {
            if (!isset($albums[$id]) || isset($visited[$id])) { return null; }
            $visited[$id] = true;
            $branch = [];
            foreach ($children[$id] ?? [] as $childId) {
                $node = $build($childId);
                if ($node) { $branch[] = $node; }
            }
            $album = $albums[$id];
            // Галерея может быть родителем документов, но её фотографии в дерево не попадают.
            if ($album->mode !== 'files' && !$branch) { return null; }
            return $nodes[$id] = (object) [
                'album' => $album,
                'params' => H::settings(json_decode($album->params ?: '{}', true) ?: []),
                'files' => [],
                'children' => $branch,
            ];
        };
        $branches = [];
        foreach ($parentId ? [$parentId] : ($children[0] ?? []) as $id) {
            $node = $build($id);
            if ($node) { $branches[] = $node; }
        }
        $ids = array_keys(array_filter($nodes, static fn($node) => $node->album->mode === 'files'));
        if ($ids) {
            $query = $db->getQuery(true)->select('*')->from('#__dharmagallery_photos')
                ->where('album_id IN (' . implode(',', array_map('intval', $ids)) . ')')
                ->where('published=1')->where('access IN (' . H::levels() . ')')
                ->order('ordering ASC')->order('id ASC');
            foreach ($db->setQuery($query)->loadObjectList() as $file) { $nodes[(int) $file->album_id]->files[] = $file; }
        }
        $parentNode = $parentId ? ($nodes[$parentId] ?? null) : null;
        return [
            'parent' => $parentId ? ($albums[$parentId] ?? null) : null,
            'params' => $parentNode?->params ?? H::settings([]),
            'files' => $parentNode?->files ?? [],
            'children' => $parentId ? ($parentNode?->children ?? []) : $branches,
        ];
    }
}
