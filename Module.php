<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   [Source] http://github.com/zionsg/ZnZend
 */
namespace ZnZend;

class Module
{
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

    public function getServiceConfig()
    {
        return array(
            // Any object pulled from a service manager is ran through the registered initializers
            'initializers' => array(
                function ($instance, $sm) {
                    // Sets default db adapter
                    // Instance needs to implement the interface and be pulled from a service manager for this to work
                    // Example in controller action:
                    //     $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                    if ($instance instanceof \Zend\Db\Adapter\AdapterAwareInterface) {
                        $instance->setDbAdapter($sm->get('Zend\Db\Adapter\Adapter'));
                    }
                }
            ),
        );
    }
}
