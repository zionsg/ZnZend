<?php
/**
 * ZnZend
 *
 * @link https://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Mvc\Controller\Plugin\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZnZend\Mvc\Controller\Plugin\ZnZendIdentity;

class ZnZendIdentityFactory implements FactoryInterface
{
    /**
     * Inject authentication service into controller plugin
     *
     * ZnZend\Authentication\AuthenticationService is used to prevent collision
     * with Zend\Authentication\AuthenticationService even though the former does not exist.
     * An entry has been put in module.config.php under ['service_manager']['invokables']
     * to use the Zend service when the ZnZend service is called.
     *
     * @return ZnZendIdentity
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $services = $serviceLocator->getServiceLocator();
        $helper = new ZnZendIdentity();
        if ($services->has('ZnZend\Authentication\AuthenticationService')) {
            $helper->setAuthenticationService($services->get('ZnZend\Authentication\AuthenticationService'));
        }
        return $helper;
    }
}
