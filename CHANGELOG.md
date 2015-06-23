# Change Log
All notable changes to this project will be documented in this file, in reverse chronological order by release.
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

## 0.1.0 - 2015-05-18
### Added
- First official release for this project.

[unreleased]: https://github.com/zionsg/ZnZend/compare/master...develop
