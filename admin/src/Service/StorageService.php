<?php
namespace Dharma\Component\Gallery\Administrator\Service;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/** Хранение и удаление только файлов, загруженных самим компонентом. */
final class StorageService
{
    public function __construct(private DatabaseInterface $db) {}

    public static function directory(string $directory): string
    {
        $directory = trim($directory, " /\t\n\r\0\x0B");
        if (!preg_match('#^images/[\p{L}\p{N}_() -]+(?:/[\p{L}\p{N}_() -]+)*$#u', $directory) || strlen($directory) > 600) {
            throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
        }
        return $directory;
    }

    /** Запрещаем исполнение серверных скриптов в настраиваемой папке загрузок. */
    public static function protectDirectory(string $directory): void
    {
        $directory = self::directory($directory);
        $root = realpath(JPATH_ROOT . '/images');
        if (!$root) {
            throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
        }

        $cursor = $root;
        foreach (array_slice(explode('/', $directory), 1) as $segment) {
            $next = $cursor . '/' . $segment;
            if (is_link($next)) {
                throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
            }
            if (!is_dir($next) && !Folder::create($next)) {
                throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED');
            }
            $real = realpath($next);
            if (!$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
                throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
            }
            $cursor = $real;
        }

        $file = $cursor . '/.htaccess';
        if (is_link($file)) {
            throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
        }
        $start = '# BEGIN Dharma Gallery upload protection';
        $end = '# END Dharma Gallery upload protection';
        $rules = <<<HTACCESS
{$start}
<IfModule mod_authz_core.c>
    <FilesMatch "(?i)\.(?:php[0-9]*|phtml|phar|cgi|pl|py|sh|shtml|asp|aspx|jsp)(?:\.|$)">
        Require all denied
    </FilesMatch>
</IfModule>
{$end}
HTACCESS;

        $contents = is_file($file) ? file_get_contents($file) : '';
        if ($contents === false) {
            throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED');
        }
        $originalContents = $contents;
        $startAt = strpos($contents, $start);
        if ($startAt !== false) {
            $endAt = strpos($contents, $end, $startAt + strlen($start));
            if ($endAt === false) {
                throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY');
            }
            $contents = substr($contents, 0, $startAt) . $rules . substr($contents, $endAt + strlen($end));
        } else {
            $contents = rtrim($contents) . ($contents !== '' ? "\n\n" : '') . $rules . "\n";
        }

        if ($contents === $originalContents) {
            return;
        }

        if (file_put_contents($file, $contents, LOCK_EX) === false) {
            throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED');
        }
    }

    public static function albumDirectory(int $albumId): string
    {
        if ($albumId <= 0) { throw new \InvalidArgumentException('COM_DHARMAGALLERY_INVALID_ALBUM'); }
        $uploadDirectory = self::directory((string) ComponentHelper::getParams('com_dharmagallery')->get('upload_directory', 'images/dharmagallery'));
        self::protectDirectory($uploadDirectory);
        $relative = $uploadDirectory . '/' . $albumId;
        $root = realpath(JPATH_ROOT . '/images');
        if (!$root) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY'); }
        $cursor = $root;
        foreach (array_slice(explode('/', $relative), 1) as $segment) {
            $next = $cursor . '/' . $segment;
            if (is_link($next)) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY'); }
            if (!is_dir($next) && !Folder::create($next)) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
            $real = realpath($next);
            if (!$real || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_DIRECTORY'); }
            $cursor = $real;
        }
        return $relative;
    }

    public function register(string $path, int $albumId): void
    {
        $path = MediaHelper::path($path);
        $hash = hash('sha256', $path);
        if ($this->db->setQuery('SELECT path_hash FROM #__dharmagallery_uploads WHERE path_hash=' . $this->db->quote($hash))->loadResult()) { return; }
        $record = (object) ['path_hash' => $hash, 'path' => $path, 'album_id' => $albumId, 'created' => Factory::getDate()->toSql()];
        $this->db->insertObject('#__dharmagallery_uploads', $record);
    }

    private function referenced(string $path): bool
    {
        $q = $this->db->quote($path);
        if ($this->db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_photos WHERE path=' . $q)->loadResult()) { return true; }
        $withFragment = $this->db->quote($this->db->escape($path, true) . '#%', false);
        if ($this->db->setQuery('SELECT COUNT(*) FROM #__dharmagallery_albums WHERE cover=' . $q . ' OR cover LIKE ' . $withFragment)->loadResult()) { return true; }
        // Ссылки в материалах, полях и модулях Joomla также сохраняют оригинал.
        foreach ([
            ['#__dharmagallery_albums', ['introtext', 'description', 'params']],
            ['#__content', ['introtext', 'fulltext', 'images']],
            ['#__fields_values', ['value']],
            ['#__modules', ['content', 'params']],
        ] as [$table, $columns]) {
            $column = "CONCAT_WS(' '," . implode(',', array_map([$this->db, 'quoteName'], $columns)) . ')';
            $conditions = [];
            $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
            foreach (array_unique([$path, $encoded, str_replace('/', '\\/', $path), str_replace('/', '\\/', $encoded), substr(json_encode($path), 1, -1)]) as $variant) {
                $conditions[] = $column . ' LIKE ' . $this->db->quote('%' . $this->db->escape($variant, true) . '%', false);
            }
            if ($this->db->setQuery('SELECT COUNT(*) FROM ' . $table . ' WHERE (' . implode(' OR ', $conditions) . ')')->loadResult()) { return true; }
        }
        return false;
    }

    public function deleteUnreferenced(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (!$path) { continue; }
            try {
                $path = MediaHelper::path($path);
                $hash = $this->db->quote(hash('sha256', $path));
                $owned = $this->db->setQuery('SELECT path FROM #__dharmagallery_uploads WHERE path_hash=' . $hash)->loadResult();
                if ($owned !== $path || $this->referenced($path)) { continue; }
                if (is_file(JPATH_ROOT . '/' . $path)) {
                    $absolute = MediaHelper::absolute($path);
                    if (!File::delete($absolute)) { throw new \RuntimeException('Cannot delete file'); }
                }
                // Имена новых копий содержат хэш исходного пути; удаляем только их.
                foreach (glob(JPATH_ROOT . '/images/dharmagallery-cache/' . sha1($path) . '-*') ?: [] as $cached) {
                    if (is_file($cached) && !is_link($cached)) { File::delete($cached); }
                }
                $this->db->setQuery('DELETE FROM #__dharmagallery_uploads WHERE path_hash=' . $hash)->execute();
            } catch (\Throwable $e) {
                Factory::getApplication()->enqueueMessage(Text::sprintf('COM_DHARMAGALLERY_DELETE_FAILED', htmlspecialchars((string) $path, ENT_QUOTES, 'UTF-8')), 'warning');
            }
        }
    }
}
