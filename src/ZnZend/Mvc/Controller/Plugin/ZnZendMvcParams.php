<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Mvc\Router\Http\RouteMatch;

/**
 * Controller plugin to resolve name of module, controller, action
 *
 * In Zend Framework 1, the 3 names could be retrieved from the request using
 * getModuleName(), getControllerName() and getActionName()
 *
 * Those methods no longer exist in Zend Framework 2, plus the controller name
 * retrieved from RouteMatch includes its namespace, different from ZF1, hence this plugin
 *
 * All names will be returned in lowercase
 */
class ZnZendMvcParams extends AbstractPlugin
{
    /**
     * Action name
     *
     * @var string
     */
    protected $actionName;

    /**
     * Controller name
     *
     * Not named $controller as conflict with AbstractPlugin
     *
     * @var string
     */
    protected $controllerName;

    /**
     * Module name
     *
     * @var string
     */
    protected $moduleName;

    /**
     * Resource id for current action controller, format is module.controller.name
     *
     * @var string
     */
    protected $resourceId;

    /**
     * Current RouteMatch
     *
     * @var RouteMatch
     */
    protected $routeMatch;

    /**
     * Get action name in lowercase
     *
     * @return string
     */
    public function getActionName()
    {
        if (null === $this->actionName) {
            $this->actionName = $this->getRouteMatch()->getParam('action');
        }

        return strtolower($this->actionName);
    }

    /**
     * Get controller name in lowercase
     *
     * Assumes controller name from route uses the following format:
     *    <module namespace>\Controller\<controller name>
     *
     * @return string
     */
    public function getControllerName()
    {
        if (null === $this->controllerName) {
            $controllerWithNs = $this->getRouteMatch()->getParam('controller');
            $tokens = explode('\\Controller\\', $controllerWithNs);
            if (!empty($tokens)) {
                $this->controllerName = end($tokens);
            }
        }

        return strtolower($this->controllerName);
    }

    /**
     * Get module name in lowercase
     *
     * Assumes controller name from route uses the following format:
     *    <module namespace>\Controller\<controller name>
     *
     * @return string
     */
    public function getModuleName()
    {
        if (null === $this->moduleName) {
            $controllerWithNs = $this->getRouteMatch()->getParam('controller');
            $tokens = explode('\\Controller\\', $controllerWithNs);
            if (!empty($tokens)) {
                $this->moduleName = reset($tokens);
            }
        }

        return strtolower($this->moduleName);
    }

    /**
     * Get resource id for current action controller
     *
     * @return string Format: module.controller.action
     */
    public function getResourceId()
    {
        if (null === $this->resourceId) {
            $this->resourceId = sprintf(
                '%s.%s.%s',
                $this->getModuleName(),
                $this->getControllerName(),
                $this->getActionName()
            );
        }

        return $this->resourceId;
    }

    /**
     * Get current RouteMatch
     *
     * @return RouteMatch
     */
    protected function getRouteMatch()
    {
        if (null === $this->routeMatch) {
            $this->routeMatch = $this->getController()->getEvent()->getRouteMatch();
        }

        return $this->routeMatch;
    }
}
