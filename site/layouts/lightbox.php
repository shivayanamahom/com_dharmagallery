<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>
<div class="modal fade dg-lightbox" id="dg-lightbox" tabindex="-1" aria-labelledby="dg-lightbox-title" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><h2 id="dg-lightbox-title" class="modal-title h5"></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button></div>
<div class="modal-body text-center"><img data-dg-full alt="" class="img-fluid"></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-dg-prev><?php echo Text::_('JPREVIOUS'); ?></button><button type="button" class="btn btn-secondary" data-dg-next><?php echo Text::_('JNEXT'); ?></button></div>
</div></div></div>
