<?php
return array(
    'controller_plugins' => array(
        'invokables' => array(
            'ZnZendMvcParams' => 'ZnZend\Mvc\Controller\Plugin\ZnZendMvcParams',
            'ZnZendPageStore' => 'ZnZend\Mvc\Controller\Plugin\ZnZendPageStore',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'znZendColumnizeEntities' => 'ZnZend\View\Helper\ZnZendColumnizeEntities',
            'znZendExcerpt'           => 'ZnZend\View\Helper\ZnZendExcerpt',
            'znZendFormatBytes'       => 'ZnZend\View\Helper\ZnZendFormatBytes',
            'znZendFormatDateRange'   => 'ZnZend\View\Helper\ZnZendFormatDateRange',
            'znZendFormatTimeRange'   => 'ZnZend\View\Helper\ZnZendFormatTimeRange',
        ),
    ),
);
