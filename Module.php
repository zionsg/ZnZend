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
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

/**
 * Methods are listed according to loading order
 *
 * @see Zend\ModuleManager\Listener\DefaultListenerAggregate::attach() for order of default MVC events
 * @see Zend\Mvc\Service\ModuleManagerFactory and Zend\ModuleManager\Feature\*Interface.php for list of config methods
 */
class Module implements
    AutoloaderProviderInterface,
    BootstrapListenerInterface,
    ConfigProviderInterface,
    InitProviderInterface
{
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
     * Defined by InitProviderInterface; Initialize workflow
     *
     * This can be used to load module-specific layouts and pass variables to controller/view during module init.
     *
     * @param  ModuleManagerInterface $manager
     * @return void
     */
    public function init(ModuleManagerInterface $manager)
    {
        // For reference only - up to application to implement

        // $manager->getEventManager()->getSharedManager()->attach(
            // array(__NAMESPACE__, 'Web', 'Cms'), // apply event to these modules (last 2 are examples)
            // MvcEvent::EVENT_DISPATCH,
            // function (MvcEvent $e) {
                // $controller = $e->getTarget();
                // $controller->layout('/layout/layout'); // load module-specific layouts
                // $controller->myVar = 'My Var'; // pass variables to controller (use $this->myVar when in controller)
                // $e->getViewModel()->setVariables(array( // pass variables to layout and view (use $myVar when in view)
                    // 'myVar' => 'My Var',
                // ));
            // },
            // 100
        // );
    }

    /**
     * Defined by BootstrapListenerInterface; Listen to the bootstrap event
     *
     * $e in this case is usually an instance of Zend\Mvc\MvcEvent.
     *
     * @param  EventInterface $e
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        // Methods called on $e below belong to MvcEvent and are not found in EventInterface
        if (!$e instanceof MvcEvent) {
            return;
        }

        $sm = $e->getApplication()->getServiceManager();

        // Allow configuration of PHP settings via 'php_settings' key in config
        $config = $sm->get('Config');
        $phpSettings = isset($config['php_settings']) ? $config['php_settings'] : array();
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

        // Another way to assign variables to layout and view other than in init()
        // $e->getViewModel()->setVariables(array(
            // 'myVar' => 'My Var',  // use $myVar when in view
        // ));
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
