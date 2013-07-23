<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend;

class Module
{
    /**
     * Set global/static db adapter for feature-enabled TableGateways such as ZnZend\Model\AbstractMapper
     *
     * Code below is for example only. It is up to the user to set it as the service manager key for the
     * database adapter may be different.
     */
    // public function onBootstrap(\Zend\Mvc\MvcEvent $e)
    // {
        // $sm = $e->getApplication()->getServiceManager();
        // if ($sm->has('Zend\Db\Adapter\Adapter')) {
            // \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter(
                // $sm->get('Zend\Db\Adapter\Adapter')
            // );
        // }
    // }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
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
