# Change Log
All notable changes to this project will be documented in this file.
It follows [Keep a CHANGELOG](http://keepachangelog.com/) as recommended by the
[Zend Framework Maintainers Guide](https://github.com/zendframework/maintainers/blob/master/MAINTAINERS.md).
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]
### Added
- This CHANGELOG file.

### Changed
- [Commit](https://github.com/zionsg/ZnZend/commit/c1e01417e68550e3cc748e87ef0c71095fc6bbfe) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to work with both version 1.9 and 1.10 of the
  [jQuery DataTables plugin](http://datatables.net/).
- [Commit](https://github.com/zionsg/ZnZend/commit/4726c6ea7fe77f9e8b06392a6c6987ff688810bb) updates
  `ZnZend\Mvc\Controller\Plugin\ZnZendDataTables` to process the global search in addition to the
  individual column filters.

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

## 0.1.0 - 2015-05-18
### Added
- First official release for this project.

[unreleased]: https://github.com/zionsg/ZnZend/compare/master...develop
