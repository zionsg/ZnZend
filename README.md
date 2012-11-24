ZnZend
======

Zend Framework 2 module containing helpers and base classes for my projects

## Introduction

This is an attempt to build up a Zend Framework 2 module containing revamps of
the helpers and base classes I used for my Zend Framework 1 projects

## Requirements

* PHP 5.3.3 and above
* Zend Framework 2

## Installation

1. Clone this project into your `./vendor/` directory and enable it in your
   `application.config.php` file under the `modules` key.
2. Examples can be found in the `examples` directory.

Classes
-------
* `ZnZend\Model\AbstractEntity` - An abstract entity class

View Helpers
------------
* `znZendColumnizeEntities` - Output entities in columns
* `znZendExcerpt` - Extract excerpt from text
* `znZendFormatBytes` - Format bytes to human-readable form
* `znZendFormatDateRange` - Format a date range
* `znZendFormatTimeRange` - Format a time range