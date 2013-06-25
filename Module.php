<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend;

use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\Mvc\MvcEvent;

class Module
{
    /**
     * Set global/static db adapter for feature-enabled TableGateways such as ZnZend\Model\AbstractTable
     */
    public function onBootstrap(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        if ($sm->has('Zend\Db\Adapter\Adapter')) {
            GlobalAdapterFeature::setStaticAdapter($sm->get('Zend\Db\Adapter\Adapter'));
        }
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig($env = null)
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
