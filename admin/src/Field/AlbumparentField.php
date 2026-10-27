<?php
namespace Dharma\Component\Gallery\Administrator\Field;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
class AlbumparentField extends ListField
{
    protected $type = 'Albumparent';
    protected function getOptions()
    {
        $options = parent::getOptions();
        $currentId = (string) $this->element['exclude_current'] === 'false' ? 0 : (int) $this->form->getValue('id');
        $publishedOnly = (string) $this->element['published_only'] !== 'false';
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $rows = $db->setQuery('SELECT id,title,parent_id,ordering,published FROM #__dharmagallery_albums ORDER BY ordering,title,id')->loadObjectList('id');
        $children = [];
        foreach ($rows as $row) { $children[(int) $row->parent_id][] = $row; }
        $excluded = [];
        $exclude = function (int $id) use (&$exclude, &$excluded, $children): void {
            if (isset($excluded[$id])) { return; }
            $excluded[$id] = true;
            foreach ($children[$id] ?? [] as $child) { $exclude((int) $child->id); }
        };
        if ($currentId) { $exclude($currentId); }
        $eligible = static function (int $id) use ($rows, $publishedOnly): bool {
            if (!$publishedOnly) { return true; }
            $seen = [];
            while ($id > 0) {
                if (!isset($rows[$id]) || isset($seen[$id]) || (int) $rows[$id]->published !== 1) { return false; }
                $seen[$id] = true; $id = (int) $rows[$id]->parent_id;
            }
            return true;
        };
        $visited = [];
        $append = function (int $parent, int $depth = 0) use (&$append, &$options, &$visited, $children, $eligible, $excluded): void {
            foreach ($children[$parent] ?? [] as $row) {
                $id = (int) $row->id;
                if (isset($excluded[$id]) || isset($visited[$id]) || !$eligible($id)) { continue; }
                $visited[$id] = true;
                $options[] = (object) ['value' => $id, 'text' => str_repeat('— ', $depth) . $row->title];
                $append($id, $depth + 1);
            }
        };
        $append(0);
        if (!$publishedOnly) {
            foreach ($rows as $id => $row) {
                if (!isset($visited[$id]) && !isset($excluded[$id])) {
                    $options[] = (object) ['value' => $id, 'text' => $row->title]; $visited[$id] = true; $append((int) $id, 1);
                }
            }
        }
        // Существующая связь с недоступным родителем показывается только как закреплённое значение.
        $savedParent = (int) ($rows[$currentId]->parent_id ?? 0);
        if ($savedParent && isset($rows[$savedParent]) && !isset($visited[$savedParent]) && (int) $this->value === $savedParent) {
            $options[] = (object) ['value' => $savedParent, 'text' => $rows[$savedParent]->title . ' (' . Text::_('COM_DHARMAGALLERY_PARENT_UNAVAILABLE') . ')', 'disable' => true];
        }
        return $options;
    }
}
