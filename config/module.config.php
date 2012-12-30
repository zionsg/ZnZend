<?php
return array(
    'controller_plugins' => array(
        'invokables' => array(
            'znZendMvcParams' => 'ZnZend\Mvc\Controller\Plugin\ZnZendMvcParams',
            'znZendPageStore' => 'ZnZend\Mvc\Controller\Plugin\ZnZendPageStore',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'znZendColumnizeEntities' => 'ZnZend\View\Helper\ZnZendColumnizeEntities',
            'znZendExcerpt'           => 'ZnZend\View\Helper\ZnZendExcerpt',
            'znZendFormatBytes'       => 'ZnZend\View\Helper\ZnZendFormatBytes',
            'znZendFormatDateRange'   => 'ZnZend\View\Helper\ZnZendFormatDateRange',
            'znZendFormatTimeRange'   => 'ZnZend\View\Helper\ZnZendFormatTimeRange',
            // Form view helpers
            'znZendFormRow'           => 'ZnZend\Form\View\Helper\ZnZendFormRow',
            'znZendFormTable'         => 'ZnZend\Form\View\Helper\ZnZendFormTable',
        ),
    ),
);
