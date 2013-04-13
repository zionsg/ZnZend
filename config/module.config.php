<?php
return array(
    'controller_plugins' => array(
        'invokables' => array(
            'znZendDataTables' => 'ZnZend\Mvc\Controller\Plugin\ZnZendDataTables',
            'znZendMvcParams'  => 'ZnZend\Mvc\Controller\Plugin\ZnZendMvcParams',
            'znZendPageStore'  => 'ZnZend\Mvc\Controller\Plugin\ZnZendPageStore',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'znZendColumnizeEntities' => 'ZnZend\View\Helper\ZnZendColumnizeEntities',
            'znZendExcerpt'           => 'ZnZend\View\Helper\ZnZendExcerpt',
            'znZendFlashMessages'     => 'ZnZend\View\Helper\ZnZendFlashMessages',
            'znZendFormatBytes'       => 'ZnZend\View\Helper\ZnZendFormatBytes',
            'znZendFormatDateRange'   => 'ZnZend\View\Helper\ZnZendFormatDateRange',
            'znZendFormatTimeRange'   => 'ZnZend\View\Helper\ZnZendFormatTimeRange',
            // Form view helpers
            'znZendFormRow'           => 'ZnZend\Form\View\Helper\ZnZendFormRow',
            'znZendFormTable'         => 'ZnZend\Form\View\Helper\ZnZendFormTable',
        ),
    ),

    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy', // required for returning of JsonModel to work
        ),
    ),
);
