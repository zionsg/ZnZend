# Change Log

All notable changes to this project will be documented in this file, in reverse chronological order by release.
The format follows [Keep a CHANGELOG](http://keepachangelog.com/) as recommended by the
[Zend Framework Maintainers Guide](https://github.com/zendframework/maintainers/blob/master/MAINTAINERS.md).
BC breaks shall be listed at the top of their respective sections.
This project adheres to [Semantic Versioning](http://semver.org/).

## [v0.3.0] - 2017-12-30
Migration from Zend Framework 2 to Zend Framework 3. This release updates the module to work with Zend Framework 3 (ZF3)
projects, with intzone.com being its first deployment. Not all the components have been tested yet - it is still
work in progress.

As of this point, the module will no longer maintain compatibility with Zend Framework 2 (ZF2).
For ZF2 projects, please use the `v0.2.0` release.

### Added
- Nothing.

### Changed
- Use Composer for installation, including specifying of individual Zend Framework components.
- Require PHP 7.0 and above.
- Use [PSR-4](http://www.php-fig.org/psr/psr-4/) for autoloading and directory structure. Path in
  `ZnZend\Module::getConfig()` updated accordingly.
- Short array syntax used to define arrays.
- Replace code such as `(isset($x) ? $x : $y)` with null coalescing operator `??`.
- Replace [Sensio Labs PHP CS Fixer](https://github.com/fabpot/php-cs-fixer) with
  [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) for checking compliance with coding standards.
- Replace [PHPUnit](https://phpunit.de/) with [Zend\Test](https://docs.zendframework.com/zend-test/) for tests.
- Replace all occurrences of `Zend\Stdlib\Hydrator\ArraySerializable` with `Zend\Hydrator\ArraySerializable`.
- Add method argument `$priority` to `ZnZend\Listener\LogListener::attach()` due to method signature change in
  `Zend\EventManager\ListenerAggregateInterface::attach()`.
- Replace `$eventManager->attach($logListener);` with `$logListener->attach($eventManager);` in commented example
  for the log listener in `ZnZend\Module::onBootstrap()`.

### Removed
- `ZnZend\Module` no longer implements `Zend\ModuleManager\Feature\AutoloaderProviderInterface`
  nor `getAutoloaderConfig()`.
- Removed commented example for `Zend\Mvc\ModuleRouteListener` in `ZnZend\Module::onBootstrap()`.

### Fixed
- Lots of CS fixes :P
- Travis CI was failing due to support for PHP 5.3 and broken link for Sensio Lab's PHP CS Fixer. Now working.
- Pass each event as a string via a loop to `ZnZend\Listener\LogListener::attach()` for the 2nd argument
  as `Zend\EventManager\SharedEventManager::attach()` no longer accepts an array of events for the 2nd argument.

## [v0.2.0] - 2015-10-14

### Added
- This CHANGELOG file.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/9b7c6746742852d1c4eac725ce769b4b09a6e8d9) adds a new method
  `fetchIn()` in `ZnZend\Db\MapperInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/ca5d3ff23f6fe1b444f23dd1333e908e746432f2) adds new method `fetchIn()`
  to `ZnZend\Db\AbstractMapper` as required by `ZnZend\Db\MapperInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/b3dcdcc4a76928b32f0f4876467bc4ccfc006683) adds `$webRoot` optional
  method argument to `ZnZend\View\Helper\ZnZendResizeImage::__invoke()`.

### Changed
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/e3d66d9d290f4aaca54e422e3b2d11c33d41dbf0) adds a new
  argument to `__invoke()` for `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` for use in global search but changes
  argument order.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/ad538b5dd5c5bf85db2961380d5a097fa1779086) adds a new
  argument to `ZnZend\Db\Generator\EntityGenerator::generate()` to allow different extending class but changes argument
  order.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/329d251b6af09fc8e12c79ff4cabe939c360acf1) adds a new
  argument to `ZnZend\Db\Generator\MapperGenerator::generate()` to allow different extending class but changes argument
  order.
- [Commit](https://github.com/zionsg/ZnZend/commit/c1e01417e68550e3cc748e87ef0c71095fc6bbfe) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to work with both version 1.9 and 1.10 of the
  [jQuery DataTables plugin](http://datatables.net/).
- [Commit](https://github.com/zionsg/ZnZend/commit/4726c6ea7fe77f9e8b06392a6c6987ff688810bb) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to process the global search in addition to the
  individual column filters.
- [Commit](https://github.com/zionsg/ZnZend/commit/9ed55f40d30e736bba4e53bd91402be7a83844da) adds
  annotations for priority to entity properties in `ZnZend\Db\Generator\EntityGenerator`.
- [Commit](https://github.com/zionsg/ZnZend/commit/837031becb37eacda6df9452b025958447c2ecc1) changes return values for
  elements in `ZnZend\Form\View\Helper\ZnZendFormValue::render()`. No more `nl2br()` for textarea value.
- [Commit](https://github.com/zionsg/ZnZend/commit/32c24edc9da9fd9e0e8e0bee497e66a7f6c18c65) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to allow specifying of search operators (DataTables 1.10 only).
- [Commit](https://github.com/zionsg/ZnZend/commit/789fb150dad1844e128cb098f44ac76b1bf289cd) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to use `Zend\Db\Sql\Expression` when sorting columns.

### Fixed
- [Commit](https://github.com/zionsg/ZnZend/commit/ec6b932aa18fafcf5428a1a3bd8df0231b4a44d2) fixes
  `ZnZend\Listener\LogListener::logException()` with a check on the existence of the `exception` property in the result.
  In some instances where the call is forwarded to an error page, the result might give a 302 status without setting
  the exception.
- [Commit](https://github.com/zionsg/ZnZend/commit/137907adaeef4df21a49c303e587f16ca2f34003) updates
  `ZnZend\Db\AbstractMapper::create()` not to re-create the entity if `$set` is already of type `EntityInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/b2b0d694fedc806f5bb829f761cbfcc7838fb803) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to use the HAVING clause for global search and column filtering as
  column aliases are not allowed in the WHERE clause.
- [Commit](https://github.com/zionsg/ZnZend/commit/4183045083b08dc434c140bc6990b18b74abce02) fixes
  annotations for priority in `ZnZend\Db\Generator\EntityGenerator` to correctly assign larger numbers for higher
  priority.
- [Commit](https://github.com/zionsg/ZnZend/commit/dfbc4ad71aabe30b7e42ba554fc4c20d63731c3a) adds check for empty
  timestamp in `ZnZend\Db\AbstractEntity::set()`.
- [Commit](https://github.com/zionsg/ZnZend/commit/56919088d16ff624b9552bb8344c890f3de0fc55) fixes the return value for
  the Select element in `ZnZend\Form\View\Helper\ZnZendFormValue` to return the corresponding option.
- [Commit](https://github.com/zionsg/ZnZend/commit/94a00aa3d1b69f0c9a0c23899a62cc666bcb7dea) fixes the mapping of the
  decimal and real SQL types to PHP float type in `ZnZend\Db\Generator\EntityGenerator::$mapTypes`.
- [Commit](https://github.com/zionsg/ZnZend/commit/b0e9b9b73a7a1b52f480feb33da54d3a961f8528) adds `maxlength` attribute
  for CHAR types in `ZnZend\Db\Generator\EntityGenerator::generate()`.
- [Commit](https://github.com/zionsg/ZnZend/commit/87830fc48ba413934eaa1361f4bf601f578d5747) changes return value for
  file element in `ZnZend\Form\View\Helper\ZnZendFormValue::render()`.

## [v0.1.0] - 2015-05-18

This is the first official release for the ZnZend module. Though it has been used in production for about 2 years,
the developer deems it not appropriate to tag it as version 1.0.0 as the unit tests are not complete, yet the increasing
use of it warrants versioning. A develop branch has been created at this point to accumulate changes before merging them
into the master branch for a new release.

[Unreleased]: https://github.com/zionsg/ZnZend/compare/v0.3.0...HEAD
[v0.3.0]: https://github.com/zionsg/ZnZend/compare/v0.2.0...v0.3.0
[v0.2.0]: https://github.com/zionsg/ZnZend/compare/v0.1.0...v0.2.0
[v0.1.0]: https://github.com/zionsg/ZnZend/tree/v0.1.0
