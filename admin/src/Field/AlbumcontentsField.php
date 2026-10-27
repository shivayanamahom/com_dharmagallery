<?php
namespace Dharma\Component\Gallery\Administrator\Field;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
class AlbumcontentsField extends SubformField
{
    protected $type = 'Albumcontents';
    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if (!parent::setup($element, $value, $group)) { return false; }
        $album = (int) $this->form->getValue('id');
        $user = Factory::getApplication()->getIdentity();
        $buttons = ['move'];
        if ($user->authorise('core.create', MediaHelper::asset($album))) { $buttons[] = 'add'; }
        if ($user->authorise('core.delete', MediaHelper::asset($album))) { $buttons[] = 'remove'; }
        $this->__set('buttons', implode(',', $buttons));
        $limit = max(1, min(300, (int) ComponentHelper::getParams('com_dharmagallery')->get('max_items', 100)));
        $this->__set('max', max($limit, is_array($value) ? count($value) : 0));
        $mode = (string) $element['album_mode'];
        $this->__set('formsource', $this->rowForm($mode === 'files', $user->authorise('core.edit.state', MediaHelper::asset($album))));
        return true;
    }
    private function rowForm(bool $files, bool $canState): string
    {
        return '<form><field name="id" type="hidden" filter="integer" />'
            . '<field name="path" type="media" label="COM_DHARMAGALLERY_FILE" types="' . ($files ? 'images,documents' : 'images') . '" preview="tooltip" required="true" />'
            . '<field name="title" type="text" label="JGLOBAL_TITLE" maxlength="255" filter="string" />'
            . ($files ? '' : '<field name="alt" type="text" label="COM_DHARMAGALLERY_ALT" maxlength="255" filter="string" />')
            . '<field name="description" type="textarea" label="COM_DHARMAGALLERY_CAPTION" rows="2" filter="string" />'
            . '<field name="published" type="list" label="JSTATUS" default="1"' . ($canState ? '' : ' disabled="true" filter="unset"') . '><option value="1">JPUBLISHED</option><option value="0">JUNPUBLISHED</option></field></form>';
    }
}
