# Change Log
All notable changes to this project will be documented in this file.
It follows [Keep a CHANGELOG](http://keepachangelog.com/) as recommended by the
[Zend Framework Maintainers Guide](https://github.com/zendframework/maintainers/blob/master/MAINTAINERS.md).
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
### Added
- This CHANGELOG file.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/9b7c6746742852d1c4eac725ce769b4b09a6e8d9) adds a new method `fetchIn()` in `ZnZend\Db\MapperInterface`.
- [Commit](https://github.com/zionsg/ZnZend/commit/ca5d3ff23f6fe1b444f23dd1333e908e746432f2) adds new method `fetchIn()` to `ZnZend\Db\AbstractMapper` as required by `ZnZend\Db\MapperInterface`.

### Changed
- [Commit](https://github.com/zionsg/ZnZend/commit/c1e01417e68550e3cc748e87ef0c71095fc6bbfe) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to work with both version 1.9 and 1.10 of the
  [jQuery DataTables plugin](http://datatables.net/).
- [Commit](https://github.com/zionsg/ZnZend/commit/4726c6ea7fe77f9e8b06392a6c6987ff688810bb) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to process the global search in addition to the
  individual column filters.
- [Commit](https://github.com/zionsg/ZnZend/commit/9ed55f40d30e736bba4e53bd91402be7a83844da) adds
  annotations for priority to entity properties in `ZnZend\Db\Generator\EntityGenerator`.
- [BC Break Commit](https://github.com/zionsg/ZnZend/commit/e3d66d9d290f4aaca54e422e3b2d11c33d41dbf0) adds a new argument
  to `__invoke()` for `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` for use in global search but changes argument order.
- [Commit](https://github.com/zionsg/ZnZend/commit/837031becb37eacda6df9452b025958447c2ecc1) changes return values for  
  elements in `ZnZend\Form\View\Helper\ZnZendFormValue::render()`. No more `nl2br()` for textarea value.

### Deprecated
- Nothing yet.

### Removed
- Nothing yet.

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

## 0.1.0 - 2015-05-18
### Added
- First official release for this project.

[unreleased]: https://github.com/zionsg/ZnZend/compare/master...develop
