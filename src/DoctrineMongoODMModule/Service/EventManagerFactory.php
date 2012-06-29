<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */
namespace DoctrineMongoODMModule\Service;

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use DoctrineModule\Service\AbstractFactory;
use DoctrineMongoODMModule\Events;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory creates a doctrine event manager. Subscribers may come
 * from the options class or as respones to the getSubscribers event
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @since   0.1.0
 * @author  Tim Roediger <superdweebie@gmail.com>
 */
class EventManagerFactory extends AbstractFactory
{
    /**
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     * @return \Doctrine\Common\EventManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $options \DoctrineModule\Options\EventManager */
        $options = $this->getOptions($serviceLocator, 'eventmanager');
        $eventManager     = new EventManager;

        //Get subscribers from config
        $subscribers = $options->getSubscribers();

        //Get any other subscribers by triggering getSubscribers event
        $events = $serviceLocator->get('EventManager');
        $events->addIdentifiers(Events::identifier);
        $collection = $events->trigger(Events::getSubscribers, $serviceLocator);
        foreach($collection as $response) {
            $subscribers = array_merge($subscribers, $response);
        }

        foreach($subscribers as $subscriber) {
            if ($subscriber instanceof EventSubscriber) {
                $eventManager->addEventSubscriber($subscriber);
            } elseif (is_subclass_of($subscriber, 'Doctrine\Common\EventSubscriber')) {
                $eventManager->addEventSubscriber(new $subscriber);
            } else {
                $eventManager->addEventSubscriber($serviceLocator->get($subscriber));
            }
        }

        return $eventManager;
    }

    /**
     * Get the class name of the options associated with this factory.
     *
     * @return string
     */
    public function getOptionsClass()
    {
        return 'DoctrineModule\Options\EventManager';
    }
}