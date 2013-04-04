<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Zend\Session\ManagerInterface as Manager;
use Zend\Session\SessionManager;


/**
 * Controller plugin to persist data for current page across page reloads
 *
 * Background:
 *   A page lists all records from the database with pagination and has a search form
 *   When the search form is submitted, the page lists search results from the database with pagination
 *   When the user clicks page 2 of the pagination links, it shows page 2 of ALL records and not
 *   page 2 of the search results as the search form was not re-submitted
 *
 * This plugin can be used by the search form to store the search query (submitted form data) in session
 * The stored data is cleared when the user navigates to another page to prevent caching
 *
 * Basic usage uses only setData() and getData()
 */
class ZnZendPageStore extends AbstractPlugin
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Name of session container, default is 'ZnZend_PageStore'
     *
     * @var string
     */
    protected $containerName = 'ZnZend_PageStore';

    /**
     * Resource id for current page, in the format controller.action
     *
     * @var string
     */
    protected $currentResource;

    /**
     * @var Manager
     */
    protected $session;

    /**
     * Key used for retrieving data from page store, default is 'data'
     *
     * @var string
     */
    protected $storeDataKey = 'data';

    /**
     * Key used for retrieving resource id from page store, default is 'resource'
     *
     * @var string
     */
    protected $storeResourceKey = 'resource';


    /**
     * Get session container for page store
     *
     * @return Container
     */
    public function getContainer()
    {
        if ($this->container instanceof Container) {
            return $this->container;
        }

        $manager = $this->getSessionManager();
        $this->container = new Container($this->containerName, $manager);
        return $this->container;
    }

    /**
     * Get name of session container for page store
     *
     * @return string
     */
    public function getContainerName()
    {
        return $this->containerName;
    }

    /**
     * Retrieve the session manager
     *
     * If none composed, lazy-loads a SessionManager instance
     *
     * @return Manager
     */
    public function getSessionManager()
    {
        if (!$this->session instanceof Manager) {
            $this->setSessionManager(new SessionManager());
        }
        return $this->session;
    }

    /**
     * Set name of session container for page store
     *
     * @param  string $containerName
     * @return ZnZendPageStore
     */
    public function setContainerName($containerName)
    {
        $this->containerName = $containerName;
        return $this;
    }

    /**
     * Set the session manager
     *
     * @param  Manager $manager
     * @return ZnZendPageStore
     */
    public function setSessionManager(Manager $manager)
    {
        $this->session = $manager;
        return $this;
    }

    /**
     * Get stored data for current page
     *
     * @return string
     */
    public function getData()
    {
        $container = $this->getContainer();

        // Clear search query in session if it does not belong to current page
        if ($this->getCurrentResource() != $container->{$this->storeResourceKey}) {
            $container->{$this->storeResourceKey} = '';
            $container->{$this->storeDataKey} = '';
        }

        return $container->{$this->storeDataKey};
    }

    /**
     * Get resource id for current page
     *
     * @return string
     */
    protected function getCurrentResource()
    {
        if (null === $this->currentResource) {
            // Controller not available in constructor, hence placed here
            $routeMatch = $this->getController()->getEvent()->getRouteMatch();
            $this->currentResource = sprintf(
                '%s.%s',
                $routeMatch->getParam('controller'),
                $routeMatch->getParam('action')
            );
        }

        return $this->currentResource;
    }

    /**
     * Store data for current page
     *
     * For storing data from search forms, the recommended method would be to
     * store the form data
     *
     * @param  mixed $data Data to store for current page
     * @return ZnZendPageStore
     */
    public function setData($data)
    {
        $container = $this->getContainer();
        $container->{$this->storeResourceKey} = $this->getCurrentResource();
        $container->{$this->storeDataKey} = $data;
        return $this;
    }
}
