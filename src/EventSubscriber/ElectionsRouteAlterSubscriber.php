<?php

namespace Drupal\localgov_elections\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ElectionsRouteAlterSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = 'onRoutingAlterSetAdminTheme';
    return $events;
  }


  public function onRoutingAlterSetAdminTheme(RouteBuildEvent $event) {
    $routes = $event->getRouteCollection();

    if ($route = $routes->get('view.localgov_elections_contests.page_1')) {
      $route->setOption('_admin_route', TRUE);

      // Ensure admin tab only displays on election pages.
      $route->addRequirements(['_content_type' => 'localgov_election']);
    }
  }
}
