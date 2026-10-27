<?php

namespace Dharma\Component\Gallery\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

class AlbumparentField extends ListField
{
	protected $type = 'Albumparent';

	protected function getOptions()
	{
		$options = parent::getOptions();

		$currentId = (string) $this->element['exclude_current'] === 'false' ? 0 : (int) $this->form->getValue('id');

		$db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
		$query = $db->getQuery(true)
			->select(['id', 'title', 'parent_id', 'ordering'])
			->from($db->quoteName('#__dharmagallery_albums'))
			->order($db->quoteName('ordering') . ' ASC')
			->order($db->quoteName('title') . ' ASC');
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$childrenByParent = [];

		foreach ($rows as $row)
		{
			$childrenByParent[(int) $row->parent_id][] = $row;
		}

		// Нельзя назначать альбом своим дочерним альбомом: это создаст цикл
		// и сломает дерево при следующем редактировании.
		$excludedIds = [];
		$excludeBranch = function (int $id) use (&$excludeBranch, &$excludedIds, $childrenByParent): void {
			if (isset($excludedIds[$id]))
			{
				return;
			}

			$excludedIds[$id] = true;

			foreach ($childrenByParent[$id] ?? [] as $child)
			{
				$excludeBranch((int) $child->id);
			}
		};

		if ($currentId > 0)
		{
			$excludeBranch($currentId);
		}

		$visitedIds = [];
		$appendBranch = function (int $parentId, string $prefix = '', bool $rootLevel = false) use (&$appendBranch, &$visitedIds, &$options, $childrenByParent, $excludedIds): void {
			$children = $childrenByParent[$parentId] ?? [];
			$total = count($children);

			foreach ($children as $index => $row)
			{
				$id = (int) $row->id;

				if (isset($excludedIds[$id]) || isset($visitedIds[$id]))
				{
					continue;
				}

				$visitedIds[$id] = true;
				$isLast = $index === $total - 1;
				$branchPrefix = $rootLevel ? '' : $prefix . ($isLast ? '└─ ' : '├─ ');

				$options[] = (object) [
					'value' => $id,
					'text'  => $branchPrefix . $row->title,
				];

				$nextPrefix = $rootLevel ? '' : $prefix . ($isLast ? '   ' : '│  ');
				$appendBranch($id, $nextPrefix);
			}
		};

		// Сначала выводим корневые альбомы, затем их дочерние ветки.
		$appendBranch(0, '', true);

		// На случай старых записей с некорректным parent_id не теряем их из списка,
		// но показываем отдельной дочерней строкой внизу.
		foreach ($rows as $row)
		{
			$id = (int) $row->id;

			if (isset($excludedIds[$id]) || isset($visitedIds[$id]))
			{
				continue;
			}

			$visitedIds[$id] = true;
			$options[] = (object) [
				'value' => $id,
				'text'  => '└─ ' . $row->title,
			];
		}

		return $options;
	}
}
