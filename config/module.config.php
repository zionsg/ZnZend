<?php
/**
 * @see Zend\Mvc\Service\ModuleManagerFactory for list of manager keys (ie. view_helpers, etc.)
 * @see Zend\ModuleManager\Listener\ServiceListener::serviceConfigTo[) and
 *      Zend\ServiceManager\Config for list of config keys for each service manager (ie. invokables, etc.)
 * @see ZnZend\Module::onBootstrap() for configuring of PHP settings via 'php_settings' key
 */

// $isDevelopmentMode = ('development' == getenv('APP_ENV')); // whether app is running in localhost development mode
return [
    // PHP settings can be configured via this key (preferrably in /config/autoload/global.php)
    'php_settings' => [
        // 'display_startup_errors' => $isDevelopmentMode,
        // 'display_errors'  => $isDevelopmentMode,
        // 'error_reporting' => ($isDevelopmentMode ? E_ALL : E_ALL & ~E_NOTICE],
    ],

    'service_manager' => [
        'aliases' => [
            // Both the alias and invokable are required for ZnZendIdentityFactory to work
            'ZnZend\Authentication\AuthenticationService' => 'Zend\Authentication\AuthenticationService',
        ],

        'invokables' => [
            'Zend\Authentication\AuthenticationService' => 'Zend\Authentication\AuthenticationService',
        ],
    ],

    'controller_plugins' => [
        'factories'  => [
            'znZendIdentity' => 'ZnZend\Mvc\Controller\Plugin\Service\ZnZendIdentityFactory',
        ],

        'invokables' => [
            'znZendDatabaseRowSize' => 'ZnZend\Mvc\Controller\Plugin\ZnZendDatabaseRowSize',
            'znZendDataTables'      => 'ZnZend\Mvc\Controller\Plugin\ZnZendDataTables',
            'znZendMvcParams'       => 'ZnZend\Mvc\Controller\Plugin\ZnZendMvcParams',
            'znZendPageStore'       => 'ZnZend\Mvc\Controller\Plugin\ZnZendPageStore',
            'znZendRestJson'        => 'ZnZend\Mvc\Controller\Plugin\ZnZendRestJson',
            'znZendTimestamp'       => 'ZnZend\Mvc\Controller\Plugin\ZnZendTimestamp',
        ],
    ],

    'view_helpers' => [
        'invokables' => [
            'znZendColumnizeEntities' => 'ZnZend\View\Helper\ZnZendColumnizeEntities',
            'znZendContrastColor'     => 'ZnZend\View\Helper\ZnZendContrastColor',
            'znZendExcerpt'           => 'ZnZend\View\Helper\ZnZendExcerpt',
            'znZendFormatBytes'       => 'ZnZend\View\Helper\ZnZendFormatBytes',
            'znZendFormatDateRange'   => 'ZnZend\View\Helper\ZnZendFormatDateRange',
            'znZendFormatTimeRange'   => 'ZnZend\View\Helper\ZnZendFormatTimeRange',
            'znZendResizeImage'       => 'ZnZend\View\Helper\ZnZendResizeImage',
            'znZendSpanEntities'      => 'ZnZend\View\Helper\ZnZendSpanEntities',
            // Form view helpers
            'formElement'               => 'ZnZend\Form\View\Helper\ZnZendFormElement', // override default helper
            'znZendFormValue'           => 'ZnZend\Form\View\Helper\ZnZendFormValue',
            'znZendFormCaptchaQuestion' => 'ZnZend\Form\View\Helper\Captcha\Question',
        ],
    ],

    'view_manager' => [
        'strategies' => [
            'ViewJsonStrategy', // required for returning of JsonModel to work
        ],
    ],
];
