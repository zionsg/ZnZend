ZnZend
======

[![Build Status](https://secure.travis-ci.org/zionsg/ZnZend.png?branch=master)](https://travis-ci.org/zionsg/ZnZend)

Zend Framework 2 module containing helpers and base classes for my projects.

## Introduction

This is an attempt to build up a Zend Framework 2 module containing revamps of
the helpers and base classes I used for my Zend Framework 1 projects

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
* `ZnZend\Model\AbstractEntity` - An abstract entity class

Controller Plugins
------------------
* `ZnZendMvcParams` - Get name of module, controller and action as like in ZF1
* `ZnZendPageStore` - Persist data for current page across page reloads

View Helpers
------------
* `znZendColumnizeEntities` - Output entities in columns
* `znZendExcerpt` - Extract excerpt from text
* `znZendFormatBytes` - Format bytes to human-readable form
* `znZendFormatDateRange` - Format a date range
* `znZendFormatTimeRange` - Format a time range