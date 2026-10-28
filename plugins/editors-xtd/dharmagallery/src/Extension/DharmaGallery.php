<?php

namespace Dharma\Plugin\EditorsXtd\DharmaGallery\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Кнопка «Dharma Gallery» в редакторе: открывает окно со списком альбомов и вставляет код {dharmagallery id=N}.
 * Выбор передаёт штатный сценарий Joomla (postMessage joomla:content-select), своего JS у кнопки нет.
 */
final class DharmaGallery extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        $registry = $event->getButtonsRegistry();

        if (\in_array($this->_name, $event->getDisabledButtons(), true)) {
            return;
        }

        $user = $this->getApplication()->getIdentity();

        // Список альбомов открывается в админке; нужен доступ к компоненту и право правки его содержимого.
        if (!ComponentHelper::isEnabled('com_dharmagallery') || !$this->getApplication()->isClient('administrator')
            || !$user || !($user->authorise('core.manage', 'com_dharmagallery') || $user->authorise('core.edit', 'com_dharmagallery'))) {
            return;
        }

        $this->loadLanguage();

        $registry->add(new Button(
            $this->_name,
            [
                'action'  => 'modal',
                'link'    => 'index.php?option=com_dharmagallery&view=albums&layout=modal&tmpl=component&editor=' . $event->getEditorId(),
                'text'    => Text::_('PLG_EDITORS-XTD_DHARMAGALLERY_BUTTON'),
                'icon'    => 'images',
                'iconSVG' => '<svg viewBox="0 0 32 32" width="24" height="24"><path d="M4 6h24v20H4zM6 8v13l6-7 5 5 4-4 5 6V8zM21 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" fill="currentColor"/></svg>',
                'name'    => $this->_type . '_' . $this->_name,
            ]
        ));
    }
}
