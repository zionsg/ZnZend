# Change Log
All notable changes to this project will be documented in this file.
It follows [Keep a CHANGELOG](http://keepachangelog.com/) as recommended by the
[Zend Framework Maintainers Guide](https://github.com/zendframework/maintainers/blob/master/MAINTAINERS.md).
BC breaks will be listed at the top of their respective sections.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

## [0.2.0] - 2015-10-13
### Added
- This CHANGELOG file.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/9b7c6746742852d1c4eac725ce769b4b09a6e8d9) adds a new method `fetchIn()` in `ZnZend\Db\MapperInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/ca5d3ff23f6fe1b444f23dd1333e908e746432f2) adds new method `fetchIn()` to `ZnZend\Db\AbstractMapper` as required by `ZnZend\Db\MapperInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/b3dcdcc4a76928b32f0f4876467bc4ccfc006683) adds `$webRoot` optional method 
  argument to `ZnZend\View\Helper\ZnZendResizeImage::__invoke()`.

### Changed
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/e3d66d9d290f4aaca54e422e3b2d11c33d41dbf0) adds a new argument
  to `__invoke()` for `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` for use in global search but changes argument order.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/ad538b5dd5c5bf85db2961380d5a097fa1779086) adds a new argument to
  `ZnZend\Db\Generator\EntityGenerator::generate()` to allow different extending class but changes argument order.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/329d251b6af09fc8e12c79ff4cabe939c360acf1) adds a new argument to
  `ZnZend\Db\Generator\MapperGenerator::generate()` to allow different extending class but changes argument order.
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
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to use the HAVING clause for global search and column filtering as column aliases are not allowed in the WHERE clause.
- [Commit](https://github.com/zionsg/ZnZend/commit/4183045083b08dc434c140bc6990b18b74abce02) fixes
  annotations for priority in `ZnZend\Db\Generator\EntityGenerator` to correctly assign larger numbers for higher priority.
- [Commit](https://github.com/zionsg/ZnZend/commit/dfbc4ad71aabe30b7e42ba554fc4c20d63731c3a) adds check for empty timestamp
  in `ZnZend\Db\AbstractEntity::set()`.
- [Commit](https://github.com/zionsg/ZnZend/commit/56919088d16ff624b9552bb8344c890f3de0fc55) fixes the return value for the 
  Select element in `ZnZend\Form\View\Helper\ZnZendFormValue` to return the corresponding option.
- [Commit](https://github.com/zionsg/ZnZend/commit/94a00aa3d1b69f0c9a0c23899a62cc666bcb7dea) fixes the mapping of the decimal
  and real SQL types to PHP float type in `ZnZend\Db\Generator\EntityGenerator::$mapTypes`.
- [Commit](https://github.com/zionsg/ZnZend/commit/b0e9b9b73a7a1b52f480feb33da54d3a961f8528) adds `maxlength` attribute for  
  CHAR types in `ZnZend\Db\Generator\EntityGenerator::generate()`.
- [Commit](https://github.com/zionsg/ZnZend/commit/87830fc48ba413934eaa1361f4bf601f578d5747) changes return value for  
  file element in `ZnZend\Form\View\Helper\ZnZendFormValue::render()`.

## 0.1.0 - 2015-05-18
### Added
- First official release for this project.

[unreleased]: https://github.com/zionsg/ZnZend/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/zionsg/ZnZend/compare/v0.1.0...v0.2.0
