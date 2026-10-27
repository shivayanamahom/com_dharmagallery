<?php

namespace Dharma\Component\Gallery\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

class Router extends RouterView
{
	private DatabaseInterface $db;

	public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$this->db = $db;

		// RouterView::getName() по умолчанию вычисляет имя компонента через reflection
		// по неймспейсу (ищет сегмент между "\Component\" и следующим "\") — для
		// Dharma\Component\Gallery\... это даёт "gallery", а не "dharmagallery"
		// (совпадает со старым отключённым com_gallery). Задаём имя явно.
		$this->name = 'dharmagallery';

		$albums = new RouterViewConfiguration('albums');
		$this->registerView($albums);

		$album = new RouterViewConfiguration('album');
		$album->setKey('id')->setParent($albums);
		$this->registerView($album);


		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

	public function getAlbumSegment($id, $query)
	{
		$dbQuery = $this->db->getQuery(true)
			->select($this->db->quoteName('alias'))
			->from($this->db->quoteName('#__dharmagallery_albums'))
			->where($this->db->quoteName('id') . ' = :id')
			->bind(':id', $id, ParameterType::INTEGER);
		$this->db->setQuery($dbQuery);
		$alias = (string) $this->db->loadResult();

		return [(int) $id => (int)$id . ':' . $alias];
	}

	public function getAlbumId($segment, $query)
	{
		if (preg_match('/^(\d+)[:\-]/', $segment, $match)) { return (int)$match[1]; }
        $dbQuery = $this->db->getQuery(true)
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__dharmagallery_albums'))
			->where($this->db->quoteName('alias') . ' = :alias')
			->bind(':alias', $segment)
			->order($this->db->quoteName('id') . ' ASC');
		$this->db->setQuery($dbQuery, 0, 1);
		$id = (int) $this->db->loadResult();

		if ($id > 0)
		{
			return $id;
		}

		return ctype_digit($segment) ? (int) $segment : false;
	}

}
