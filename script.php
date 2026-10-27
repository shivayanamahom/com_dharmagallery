<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
final class Com_DharmagalleryInstallerScript extends InstallerScript
{
    protected $minimumPhp = '8.3.0';
    protected $minimumJoomla = '6.0.0';
    public function postflight($type, $parent)
    {
        $this->deleteFiles = [
            '/administrator/components/com_dharmagallery/src/Controller/ItemController.php',
            '/administrator/components/com_dharmagallery/src/Controller/ItemsController.php',
            '/administrator/components/com_dharmagallery/src/Model/ItemModel.php',
            '/administrator/components/com_dharmagallery/src/Model/ItemsModel.php',
            '/administrator/components/com_dharmagallery/src/Table/ItemTable.php',
            '/administrator/components/com_dharmagallery/forms/item.xml',
            '/administrator/components/com_dharmagallery/forms/filter_items.xml',
            '/components/com_dharmagallery/src/Model/CategoryModel.php',
            '/components/com_dharmagallery/src/Service/Category.php',
            '/components/com_dharmagallery/src/Helper/ParamsHelper.php',
            '/media/com_dharmagallery/css/gallery.css',
            '/media/com_dharmagallery/js/gallery.js',
        ];
        $this->deleteFolders = [
            '/administrator/components/com_dharmagallery/src/View/Item',
            '/administrator/components/com_dharmagallery/src/View/Items',
            '/administrator/components/com_dharmagallery/tmpl/item',
            '/administrator/components/com_dharmagallery/tmpl/items',
            '/components/com_dharmagallery/src/View/Category',
            '/components/com_dharmagallery/tmpl/category',
        ];
        $this->removeFiles();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        // В промежуточной сборке файлы могли не иметь альбома. Сохраняем их в отдельном неопубликованном альбоме.
        $orphans = (int) $db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_photos p LEFT JOIN #__dharmagallery_albums a ON a.id=p.album_id WHERE a.id IS NULL')->loadResult();
        if ($orphans) {
            $album = (object) [
                'parent_id' => 0, 'title' => 'Файлы из предыдущей сборки',
                'alias' => 'recovered-files-' . bin2hex(random_bytes(4)),
                'mode' => 'files', 'published' => 0, 'params' => '{}',
            ];
            $db->insertObject('#__dharmagallery_albums', $album, 'id');
            $db->setQuery('UPDATE #__dharmagallery_photos p LEFT JOIN #__dharmagallery_albums a ON a.id=p.album_id SET p.album_id=' . (int) $album->id . ' WHERE a.id IS NULL')->execute();
        }
        // У старых альбомов ещё не было собственных Joomla assets.
        $componentAsset = Table::getInstance('Asset');
        $componentAsset->loadByName('com_dharmagallery');
        $albums = $db->setQuery('SELECT id,title FROM #__dharmagallery_albums WHERE asset_id=0')->loadObjectList();
        foreach ($albums as $album) {
            $asset = Table::getInstance('Asset');
            $asset->loadByName('com_dharmagallery.album.' . (int) $album->id);
            if (!$asset->id) {
                $asset->name = 'com_dharmagallery.album.' . (int) $album->id;
                $asset->title = $album->title;
                $asset->rules = '{}';
                $asset->setLocation((int) $componentAsset->id ?: 1, 'last-child');
                if (!$asset->check() || !$asset->store()) { throw new \RuntimeException($asset->getError()); }
            }
            $db->setQuery('UPDATE #__dharmagallery_albums SET asset_id=' . (int) $asset->id . ' WHERE id=' . (int) $album->id)->execute();
        }
        $legacy = $db->setQuery("SELECT path,album_id FROM #__dharmagallery_photos WHERE path LIKE 'images/dharmagallery/%'")->loadObjectList();
        foreach ($legacy as $file) {
            if (!preg_match('#^images/dharmagallery/(?:[0-9]+|files/[0-9]+)/[^/]+$#u', $file->path)) { continue; }
            $hash = hash('sha256', $file->path);
            if (!$db->setQuery('SELECT path_hash FROM #__dharmagallery_uploads WHERE path_hash=' . $db->quote($hash))->loadResult()) {
                $record = (object) ['path_hash' => $hash, 'path' => $file->path, 'album_id' => (int) $file->album_id];
                $db->insertObject('#__dharmagallery_uploads', $record);
            }
        }
        if (is_file(JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php')) { unlink(JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php'); }
        return true;
    }
}
