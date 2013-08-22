<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend;

use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\EventManager\EventInterface;

/**
 * @see Zend\Mvc\Service\ModuleManagerFactory for all available config methods
 */
class Module implements AutoloaderProviderInterface, BootstrapListenerInterface, ConfigProviderInterface
{
    /**
     * Defined by BootstrapListenerInterface; Listen to the bootstrap event
     *
     * $e in this case is usually an instance of Zend\Mvc\MvcEvent.
     *
     * @param EventInterface $e
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        $sm = $e->getApplication()->getServiceManager();

        // @see https://github.com/zendframework/zf2/issues/4879 for fix by alexshelkov
        // This is a temporary fix for the php5-intl dependency since ZF 2.2.2 for all view helpers.
        $helperPluginManger = $sm->get('ViewHelperManager');
        $helperPluginManger->addInitializer(function ($helper) {
            if ($helper instanceof TranslatorAwareInterface) {
                $helper->setTranslatorEnabled(false);
            }
        });

        // Set global/static db adapter for feature-enabled TableGateways such as ZnZend\Model\AbstractMapper
        // For example only - up to application to set it as the service manager key for the db adapter may be different
        // if ($sm->has('Zend\Db\Adapter\Adapter')) {
            // \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter(
                // $sm->get('Zend\Db\Adapter\Adapter')
            // );
        // }
    }

    /**
     * Defined by AutoloaderProviderInterface; Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
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

    /**
     * Defined by ConfigProviderInterface; Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig($env = null)
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
