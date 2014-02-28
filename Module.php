<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend;

use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

/**
 * @see Zend\Mvc\Service\ModuleManagerFactory and Zend\ModuleManager\Feature\*Interface.php for list of config methods
 */
class Module implements
    AutoloaderProviderInterface,
    BootstrapListenerInterface,
    ConfigProviderInterface,
    InitProviderInterface
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

        // Allow configuration of PHP settings via 'phpSettings' key in config
        $config = $sm->get('Config');
        $phpSettings = isset($config['phpSettings']) ? $config['phpSettings'] : array();
        foreach($phpSettings as $key => $value) {
            ini_set($key, $value);
        }

        // @see https://github.com/zendframework/zf2/issues/4879 for fix by alexshelkov
        // This is a temporary fix for the php5-intl dependency since ZF 2.2.2 for all view helpers.
        $helperPluginManger = $sm->get('ViewHelperManager');
        $helperPluginManger->addInitializer(function ($helper) {
            if ($helper instanceof TranslatorAwareInterface) {
                $helper->setTranslatorEnabled(false);
            }
        });

        // For reference only - up to application to implement

        // Route listener
        // $eventManager        = $e->getApplication()->getEventManager();
        // $moduleRouteListener = new ModuleRouteListener();
        // $moduleRouteListener->attach($eventManager);

        // Set global/static db adapter for feature-enabled TableGateways such as ZnZend\Model\AbstractMapper
        // if ($sm->has('Zend\Db\Adapter\Adapter')) {
            // GlobalAdapterFeature::setStaticAdapter($sm->get('Zend\Db\Adapter\Adapter'));
        // }
    }

    /**
     * Defined by InitProviderInterface; Initialize workflow
     *
     * This can be used to load module-specific layout during module init.
     *
     * @param  ModuleManagerInterface $manager
     * @return void
     */
    public function init(ModuleManagerInterface $manager)
    {
        // For reference only - up to application to implement

        // $manager->getEventManager()->getSharedManager()->attach(
            // array(__NAMESPACE__, 'Web', 'Cms'), // load module-specific layouts for these modules (last 2 are examples)
            // MvcEvent::EVENT_DISPATCH,
            // function (MvcEvent $e) {
                // $controller = $e->getTarget();
                // // Replace application layout entirely with module-specific layout
                // $controller->layout('/layout/layout');
            // },
            // 100
        // );
    }

    /**
     * Defined by AutoloaderProviderInterface; Return an array for passing to Zend\Loader\AutoloaderFactory
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
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
