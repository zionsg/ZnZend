<?php
/**
 * ZnZend
 *
 * @author Zion Ng <zion@intzone.com>
 * @link   http://github.com/zionsg/ZnZend for canonical source repository
 */

namespace ZnZend\Listener;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Log\LoggerInterface;

/**
 * A simple listener to listen to logging events
 *
 * This is used to show sample code in Module.php on how to set up a log listener.
 * This can also be easily extended by overriding the log() method.
 */
class LogListener implements ListenerAggregateInterface
{
    /**
     * Attached listeners
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * Logger is made optional for flexibility's sake as the log() method may not
     * need a full-fledged logger.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Defined in ListenerAggregateInterface; Attach one or more listeners
     *
     * Once attached, the listener will listen to the events named 'log' and the RFC5424 severity levels,
     * eg. when the following code is run in a controller:
     *     $this->getEventManager()->trigger('log', $this, array('param' => 'value'));
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $sharedEvents      = $events->getSharedManager(); // must use shared manager else it will not work
        $this->listeners[] = $sharedEvents->attach(
            '*',
            array('log', 'emerg', 'alert', 'crit', 'err', 'warn', 'notice', 'info', 'debug'),
            array($this, 'log')
        );
    }

    /**
     * Defined in ListenerAggregateInterface; Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * Write event to log
     *
     * Method must be public due to callback in attach().
     *
     * @param  EventInterface $e
     * @return void
     */
    public function log(EventInterface $e)
    {
        if (null == $this->logger) {
            return;
        }

        $logLevel = $e->getName();
        if ('log' == $logLevel) {
            $logLevel = 'info';
        }
        $params = $e->getParams();
        $this->logger->{$logLevel}(sprintf('%s: %s', $logLevel, json_encode($params)));
    }
}
