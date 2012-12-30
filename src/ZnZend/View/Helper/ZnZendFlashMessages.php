<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 * @since  2012-12-30T17:30+08:00
 */
namespace ZnZend\View\Helper;

use Zend\Mvc\Controller\Plugin\FlashMessenger;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper to retrieve messages from FlashMessenger
 */
class ZnZendFlashMessages extends AbstractHelper
{
    /**
     * __invoke
     *
     * @return array
     */
    public function __invoke()
    {
        $flashMessenger = new FlashMessenger();
        return $flashMessenger->getMessages();
    }
}
