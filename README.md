ZnZend
======

[![Build Status](https://secure.travis-ci.org/zionsg/ZnZend.png?branch=master)](https://travis-ci.org/zionsg/ZnZend)

Zend Framework 2 module containing helpers and base classes for my projects at intzone.com.

## Introduction

This is a Zend Framework 2 module containing revamps of
the helpers and base classes I used for my Zend Framework 1 projects.

## Requirements

* PHP 5.3.3 and above
* Zend Framework 2

## Installation

1. Clone this project into your `./vendor/` directory and enable it in your
   `application.config.php` file under the `modules` key
2. Examples can be found in the `examples` directory
3. Tests can be run in browser using `test/phpunit_browser.php` (see inline docblock)

Classes
-------
* `ZnZend\Form\AbstractForm` - Base form class with additional features
* `ZnZend\Model\EntityInterface` - An entity interface
* `ZnZend\Model\AbstractEntity` - An abstract entity class
* `ZnZend\Model\AbstractTable` - An abstract table gateway
* `ZnZend\Permissions\Acl\Acl` - Additional method to add a resource and its parents recursively
* `ZnZend\Permissions\Acl\Privilege` - A standardized set of constants for Acl privileges

Controller Plugins
------------------
* `znZendDataTable` - Update Paginator (DbSelect) with params sent from jQuery DataTables plugin
* `znZendMvcParams` - Get name of module, controller and action as like in ZF1
* `znZendPageStore` - Persist data for current page across reloads of the same page
* `znZendTimestamp` - Return timestamp formatted to standard length and converted to base 36

Captcha Adapters
----------------
* `ZnZend\Captcha\Question` - Captcha adapter for custom questions and answers

Form View Helpers
-----------------
* `znZendFormCaptchaQuestion` - Render captcha element using ZnZend\Captcha\Question adapter
* `znZendFormRow` - Extension to FormRow view helper to allow rendering format to be customized
* `znZendFormTable` - Render form as 2-column table

View Helpers
------------
* `znZendColumnizeEntities` - Output entities in columns
* `znZendExcerpt` - Extract excerpt from text
* `znZendFlashMessages` - Retrieve messages from FlashMessenger
* `znZendFormatBytes` - Format bytes to human-readable form
* `znZendFormatDateRange` - Format a date range
* `znZendFormatTimeRange` - Format a time range
