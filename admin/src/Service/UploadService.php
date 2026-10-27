<?php
namespace Dharma\Component\Gallery\Administrator\Service;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\MediaHelper as JoomlaMediaHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\File;

final class UploadService
{
    public function upload(array $file, int $albumId, string $mode = 'gallery'): array
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED');
        }
        $limit = max(1, min(100, (int) ComponentHelper::getParams('com_dharmagallery')->get('upload_maxsize', 20))) * 1048576;
        if (filesize($file['tmp_name']) > $limit) { throw new \RuntimeException('COM_DHARMAGALLERY_FILE_TOO_LARGE'); }
        [$stem, $extension] = MediaHelper::uploadName((string) $file['name']);
        MediaHelper::assertAllowed($stem . '.' . $extension, $mode);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($mode === 'gallery' && !str_starts_with((string) $mime, 'image/')) { throw new \RuntimeException('COM_DHARMAGALLERY_IMAGES_ONLY'); }
        if (!in_array($mime, MediaHelper::TYPES[$extension], true)) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_TYPE'); }
        ComponentHelper::getParams('com_dharmagallery')->set('restrict_uploads_extensions', implode(',', MediaHelper::allowedExtensions($mode)));
        if (!JoomlaMediaHelper::checkFileExtension($extension, 'com_dharmagallery')) { throw new \RuntimeException('COM_DHARMAGALLERY_INVALID_TYPE'); }
        $directory = StorageService::albumDirectory($albumId);
        // Эксклюзивно резервируем имя, чтобы параллельные загрузки не перезаписали другой файл.
        $destination = null;
        for ($number = 1; $number <= 10000; $number++) {
            $name = $stem . ($number > 1 ? ' (' . $number . ')' : '') . '.' . $extension;
            $relative = $directory . '/' . $name;
            $candidate = JPATH_ROOT . '/' . $relative;
            $reservation = @fopen($candidate, 'x');
            if ($reservation !== false) { fclose($reservation); $destination = $candidate; break; }
            if (!file_exists($candidate) && !is_link($candidate)) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
        }
        if ($destination === null) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
        try {
            // Native Joomla content and executable-extension checks remain enabled.
            if (!File::upload($file['tmp_name'], $destination, false, false)) { throw new \RuntimeException('COM_DHARMAGALLERY_UPLOAD_FAILED'); }
            $meta = MediaHelper::metadata($relative);
            (new StorageService(Factory::getContainer()->get(DatabaseInterface::class)))->register($relative, $albumId);
        } catch (\Throwable $e) {
            if (is_file($destination)) { File::delete($destination); }
            throw $e;
        }
        return array_merge(['path' => $relative, 'title' => pathinfo(basename(str_replace('\\', '/', (string) $file['name'])), PATHINFO_FILENAME)], $meta);
    }
}
