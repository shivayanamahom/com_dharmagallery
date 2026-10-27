<?php
namespace Dharma\Component\Gallery\Administrator\Rule;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Helper\MediaHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
class ExtensionsRule extends FormRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        try { return count(MediaHelper::parseExtensions((string) $value)) > 0; }
        catch (\InvalidArgumentException $e) { return false; }
    }
}
