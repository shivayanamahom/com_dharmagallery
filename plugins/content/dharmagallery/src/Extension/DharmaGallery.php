<?php

namespace Dharma\Plugin\Content\DharmaGallery\Extension;

\defined('_JEXEC') or die;

use Dharma\Component\Gallery\Site\Helper\GalleryHelper as H;
use Dharma\Plugin\Content\DharmaGallery\Helper\Shortcode;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Вставка альбома Dharma Gallery в материал кодом {dharmagallery id=1}. Вывод строится теми же моделью, помощником и
 * макетами, что страница компонента, поэтому права доступа, публикация, язык и переопределения шаблона действуют так же.
 */
final class DharmaGallery extends CMSPlugin implements SubscriberInterface
{
    /** Уже выведено окно просмотра Bootstrap: второй такой же блок с тем же id на странице не нужен. */
    private static bool $lightboxPrinted = false;

    public static function getSubscribedEvents(): array
    {
        return ['onContentPrepare' => 'onContentPrepare'];
    }

    public function onContentPrepare(ContentPrepareEvent $event): void
    {
        $app = $this->getApplication();

        if ($app->isClient('api')) {
            return;
        }

        $item = $event->getItem();

        if (!\is_object($item) || !property_exists($item, 'text') || !\is_string($item->text) || !Shortcode::contains($item->text)) {
            return;
        }

        $context = $event->getContext();

        // Индексатор не должен видеть служебные коды, а текст самого альбома не вставляет альбомы (нет рекурсии).
        if ($context === 'com_finder.indexer' || str_starts_with($context, 'com_dharmagallery.') || !ComponentHelper::isEnabled('com_dharmagallery')) {
            $item->text = Shortcode::strip($item->text);

            return;
        }

        $item->text = Shortcode::replace($item->text, fn (array $attributes): string => $this->render($attributes));
    }

    /**
     * @param   array{id: int, limit: int, columns: int}  $attributes
     */
    private function render(array $attributes): string
    {
        $id = $attributes['id'];

        if ($id <= 0) {
            return '';
        }

        $app      = $this->getApplication();
        $document = $app->getDocument();

        if ($document === null) {
            return '';
        }

        try {
            $app->getLanguage()->load('com_dharmagallery', JPATH_SITE);

            $factory = $app->bootComponent('com_dharmagallery')->getMVCFactory();
            $album   = $factory->createModel('Album', 'Site', ['ignore_request' => true])->getAlbum($id);

            // Недоступный, неопубликованный или удалённый альбом не выводится и ничего не раскрывает.
            if (!$album) {
                return '';
            }

            $params                = H::settings(json_decode($album->params ?: '{}', true) ?: []);
            $params['mode']        = $album->mode ?? 'gallery';
            $params['album_id']    = $id;
            $params['embedded']    = true;

            if ($attributes['columns'] > 0) {
                $params['columns'] = $attributes['columns'];
            }

            $params['viewer'] = H::viewer($params, $document);

            $limit = $attributes['limit'] > 0
                ? $attributes['limit']
                : max(1, min(Shortcode::MAX_LIMIT, (int) ComponentHelper::getParams('com_dharmagallery')->get('items_per_page', 24)));

            $model = $factory->createModel('Items', 'Site', ['ignore_request' => true]);
            $model->setState('filter.album_id', $id);
            $model->setState('list.start', 0);
            $model->setState('list.limit', $limit);
            $items = $model->getItems();

            H::assets($document, $params['viewer'] !== 'none' && \count($items) > 0, $params['viewer']);

            $html = H::layout('items', ['items' => $items, 'params' => $params]);
        } catch (\Throwable $e) {
            return '';
        }

        // Окно просмотра Bootstrap имеет фиксированный id: остальные вставки на странице используют первое.
        if (self::$lightboxPrinted) {
            $html = (string) preg_replace('~<div class="modal fade dg-lightbox".*$~s', '', $html);
        } elseif (str_contains($html, 'dg-lightbox')) {
            self::$lightboxPrinted = true;
        }

        return '<div class="dg-embed dg-embed-' . (int) $id . ' my-4">' . $html . '</div>';
    }
}
