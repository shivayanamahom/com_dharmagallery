<?php
namespace Dharma\Component\Gallery\Administrator\Rule;
defined('_JEXEC') or die;
use Dharma\Component\Gallery\Administrator\Service\StorageService;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
class UploaddirectoryRule extends FormRule
{
    public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
    {
        try { StorageService::directory((string) $value); return true; }
        catch (\InvalidArgumentException $e) { return false; }
    }
}
