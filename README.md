# ZnZend

Master:
[![Build Status](https://secure.travis-ci.org/zionsg/ZnZend.png?branch=master)](https://travis-ci.org/zionsg/ZnZend)
Develop:
[![Build Status](https://secure.travis-ci.org/zionsg/ZnZend.png?branch=develop)](https://travis-ci.org/zionsg/ZnZend)

Zend Framework 2/3 module containing helpers and base classes for my projects at intzone.com.

## Release Information
*ZnZend v0.3.0*

30 December 2017

## Updates in v0.3.0
Please see [CHANGELOG.md](CHANGELOG.md).

## Introduction
This started off as a Zend Framework 2 module containing revamps of the helpers and classes I used for my Zend
Framework 1 projects. This is a general-purpose module unlike ZfcUser and is meant to quickstart my ZF2 projects.

This module has been updated to work with PHP 7 and Zend Framework 3 from release `v0.3.0` onwards.
For the Zend Framework 2 version, use the `v0.2.0` release.

## Requirements
- [PHP](http://php.net/) >= 7.0
- [Composer](https://getcomposer.org/)
- [Zend Framework 3](https://framework.zend.com/)

## Installation
- As this module is not available on Packagist, the GitHub repository needs to be added to `composer.json` in the
  ZF3 project.

  ```
  {
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/zionsg/ZnZend"
        }
    ]
  }
  ```
- Run `composer require zionsg/ZnZend` to get the latest release in the `master` branch.
  To get the `develop` branch, run `composer require zionsg/ZnZend:dev-develop`.
- Ensure that the `ZnZend` module is enabled in `config/modules.config.php` in the ZF3 project.
- To check coding style (CS) errors, run `composer cs`.
- To run tests, run `composer test`.
- See `scripts` key in `composer.json` for other scripts.
- Examples can be found in `docs/examples`.

## Interfaces
- `ZnZend\Authentication\IdentityInterface` - Interface for identity stored in authentication service
- `ZnZend\Db\EntityInterface` - An entity interface for database rows
- `ZnZend\Db\MapperInterface` - An entity mapper interface for database tables
- `ZnZend\Captcha\Service\QuestionServiceInterface` - An interface for services providing questions for captcha
- `ZnZend\Json\JsonProviderInterface` - An interface to provide JSON representation for an object
- `ZnZend\Permissions\Acl\Role\RoleInterface` - Adds additional methods for comparing 2 roles

## Classes
- `ZnZend\Authentication\Identity` - Class for identity stored in authentication service
- `ZnZend\Crypt\Symmetric\OpenSsl` - Symmetric encryption using the OpenSSL extension
- `ZnZend\Form\AbstractForm` - Base form class with additional features
- `ZnZend\Db\AbstractEntity` - An abstract entity class for database rows
- `ZnZend\Db\AbstractMapper` - An abstract entity mapper class for database tables
- `ZnZend\Db\Generator\EntityGenerator` - For generating entity classes from tables in a database
- `ZnZend\Db\Generator\MapperGenerator` - For generating entity mapper classes from tables in a database
- `ZnZend\Paginator\Adapter\DbSelect` - Additional methods to retrieve and update Select object
- `ZnZend\Permissions\Acl\Acl` - Modified addResource() to add a resource and its parents recursively
- `ZnZend\Permissions\Acl\Privilege` - A standardized set of constants for Acl privileges
- `ZnZend\Permissions\Acl\Role\GenericRole` - Generic role that defaults to 'guest' and where smaller numbers indicate
  higher role rank

## Captcha
* `ZnZend\Captcha\Question` - Captcha adapter for custom questions and answers
* `ZnZend\Captcha\Service\MathQuestionService` - A service which provides simple arithmetic questions for captcha

## Controller Plugins
- `znZendDatabaseRowSize` - Calculate row size for each table in specified database
- `znZendDataTables` - Update Paginator (DbSelect) with params sent from jQuery DataTables plugin
- `znZendIdentity`   - Fetch the authenticated identity as an instance of IdentityInterface
  and its role as an instance of RoleInterface. When invoked, its factory will look for a service
  by the name `ZnZend\Authentication\AuthenticationService` in the `ServiceManager`, similar
  to the Zend Identity controller plugin. The service does not exist but defaults to
  `Zend\Authentication\AuthenticationService`
- `znZendMvcParams`  - Get name of module, controller and action as like in ZF1
- `znZendPageStore`  - Persist data for current page across reloads of the same page
- `znZendRestJson`   - Consume REST web service which returns JSON result
- `znZendTimestamp`  - Return timestamp formatted to standard length and converted to base 36

## Filters
- `ZnZend\Filter\File\RenameUploadWithCallback` - Allows use of custom callback to rename file uploads

## Form Elements
- `ZnZend\Form\Element\Value` - Element for displaying value only without `<input>`

## Form View Helpers
- `znZendFormCaptchaQuestion` - Render captcha element using `ZnZend\Captcha\Question` adapter
- `znZendFormElement` - Overrides `formElement` helper to handle rendering of ZnZend form elements
- `znZendFormValue` - Render value of element only without `<input>`

## Listeners
- `ZnZend\Listener\LogListener` - Simple log listener to listen to events named after RFC5424 severity levels
  and exceptions

## View Helpers
- `znZendColumnizeEntities` - Output entities in columns using `<table>`
- `znZendContrastColor` - Choose color that provides sufficient constrast when combined with specified color
- `znZendExcerpt` - Extract excerpt from text
- `znZendFormatBytes` - Format bytes to human-readable form
- `znZendFormatDateRange` - Format a date range
- `znZendFormatTimeRange` - Format a time range
- `znZendResizeImage` - Make resized copy of image and return path for use in HTML `<img>`
- `znZendSpanEntities` - Output collection of entities in columns based on Bootstrap 2 "row-fluid" and "span" classes
