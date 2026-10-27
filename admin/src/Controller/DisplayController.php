<?php

namespace Dharma\Component\Gallery\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
	protected $default_view = 'albums';
    public function display($cachable = false, $urlparams = [])
    {
        if (!in_array($this->input->getCmd('view', 'albums'), ['albums', 'album'], true)) {
            $this->setRedirect('index.php?option=com_dharmagallery&view=albums');
            return $this;
        }
        return parent::display($cachable, $urlparams);
    }
}
