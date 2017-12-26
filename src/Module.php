<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend;

use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\EventManager\EventInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Mock;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use ZnZend\Listener\LogListener;

/**
 * Methods are listed according to loading order
 *
 * Commented out code are for reference and examples.
 *
 * @see Zend\ModuleManager\Listener\DefaultListenerAggregate::attach() for order of default MVC events
 * @see Zend\Mvc\Service\ModuleManagerFactory and Zend\ModuleManager\Feature\*Interface.php for list of config methods
 */
class Module implements
    BootstrapListenerInterface,
    ConfigProviderInterface,
    InitProviderInterface
{
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
        /*
        $manager->getEventManager()->getSharedManager()->attach(
            [__NAMESPACE__, 'Web', 'Cms'], // apply event to these modules or use '*' to apply to all
            MvcEvent::EVENT_DISPATCH,
            function (MvcEvent $e) {
                $namespace = explode('\\', $e->getRouteMatch()->getParam('controller'));
                $module = $namespace[0];

                // Pass variable to controller - use $this->myVar when in controller
                $controller = $e->getTarget();
                $controller->myVar = 'My Var';

                // Module used in setTemplate hence this need not be duplicated for every module to load specific layout
                // Variable is passed to layout (top view model) - use $myVar in layout, $this->layout()->myVar in view
                $viewModel = $e->getViewModel();
                $viewModel->setTemplate(strtolower($module) . '/layout/layout');
                $viewModel->setVariables([
                    'myVar' => 'My Var'
                ]);
            },
            100
        );
        */
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
        if (! $e instanceof MvcEvent) {
            return;
        }

        $sm = $e->getApplication()->getServiceManager();

        // Allow configuration of PHP settings via 'php_settings' key in config
        $config = $sm->get('Config');
        $phpSettings = isset($config['php_settings']) ? $config['php_settings'] : [];
        foreach ($phpSettings as $key => $value) {
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
        /*
        // Route listener
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // Log listener
        $logger->registerErrorHandler() not used as notices will be logged also and be missed out in development env
        $logger->registerExceptionHandler() does not work - listener used instead to listen to dispatch error event
        $logger = new Logger();
        $logger->addWriter(new Mock());
        $logListener = new LogListener($logger);
        $eventManager->attachAggregate($logListener); // alternate way of attaching listener from above

        // Set global/static db adapter for feature-enabled TableGateways such as ZnZend\Model\AbstractMapper
        if ($sm->has('Zend\Db\Adapter\Adapter')) {
            GlobalAdapterFeature::setStaticAdapter($sm->get('Zend\Db\Adapter\Adapter'));
        }

        // Another way to assign variables to layout other than in init()
        $e->getViewModel()->setVariables([
            'myVar' => 'My Var',
        ]);
        */
    }

    /**
     * Defined by ConfigProviderInterface; Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
