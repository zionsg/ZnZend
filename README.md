#ZnZend

[![Build Status](https://secure.travis-ci.org/zionsg/ZnZend.png?branch=master)](https://travis-ci.org/zionsg/ZnZend)

Zend Framework 2 module containing helpers and base classes for my projects at intzone.com.

## Introduction

This is a Zend Framework 2 module containing revamps of
the helpers and base classes I used for my Zend Framework 1 projects.

## Requirements

*   PHP 5.3.3 and above

*   Zend Framework 2

*   Doctrine Common 2.1 and above (for use in `Zend\Form\Annotation`)

    If you downloaded Doctrine from GitHub instead of using Composer, you will need to add Doctrine
    to the autoloading namespaces in `init_autoloader.php` or `Module.php`. The same goes for any non-ZF2 modules.
    ```php
    // init_autoloader.php
    Zend\Loader\AutoloaderFactory::factory(array(
        'Zend\Loader\StandardAutoloader' => array(
            'autoregister_zf' => true,
            'namespaces' => array(
                'Doctrine' => __DIR__ . '/vendor/doctrine',
            ),
        )
    ));

    // Module.php
    public function getAutoloaderConfig()
    {
        return array(
            'ZendLoaderStandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Doctrine' => __DIR__ . '/vendor/doctrine',
                ),
            ),
        );
    }
    ```

## Installation

1. Clone this project into your `./vendor/` directory and enable it in your
   `application.config.php` file under the `modules` key
2. Examples can be found in the `examples` directory
3. Tests can be run in browser using `test/phpunit_browser.php` (see inline docblock)

## Classes
* `ZnZend\Form\AbstractForm` - Base form class with additional features
* `ZnZend\Db\EntityInterface` - An entity interface for database rows
* `ZnZend\Db\AbstractEntity` - An abstract entity class for database rows
* `ZnZend\Db\MapperInterface` - An entity mapper interface for database tables
* `ZnZend\Db\AbstractMapper` - An abstract entity mapper class for database tables
* `ZnZend\Db\Generator\EntityGenerator` - For generating entity classes from tables in a database
* `ZnZend\Db\Generator\MapperGenerator` - For generating entity mapper classes from tables in a database
* `ZnZend\Permissions\Acl\Acl` - Modified addResource() to add a resource and its parents recursively
* `ZnZend\Permissions\Acl\Privilege` - A standardized set of constants for Acl privileges

## Captcha
* `ZnZend\Captcha\Question` - Captcha adapter for custom questions and answers
* `ZnZend\Captcha\Service\QuestionServiceInterface` - An interface for services providing questions for captcha
* `ZnZend\Captcha\Service\MathQuestionService` - A service which provides simple arithmetic questions for captcha

## Controller Plugins
* `znZendDataTable` - Update Paginator (DbSelect) with params sent from jQuery DataTables plugin
* `znZendMvcParams` - Get name of module, controller and action as like in ZF1
* `znZendPageStore` - Persist data for current page across reloads of the same page
* `znZendTimestamp` - Return timestamp formatted to standard length and converted to base 36

## Form View Helpers
* `znZendFormCaptchaQuestion` - Render captcha element using ZnZend\Captcha\Question adapter
* `znZendFormElementValue` - Render value of element without input for viewing only

## View Helpers
* `znZendColumnizeEntities` - Output entities in columns
* `znZendExcerpt` - Extract excerpt from text
* `znZendFormatBytes` - Format bytes to human-readable form
* `znZendFormatDateRange` - Format a date range
* `znZendFormatTimeRange` - Format a time range
* `znZendResizeImage` - Make resized copy of image and return path for use in HTML &lt;img&gt;
