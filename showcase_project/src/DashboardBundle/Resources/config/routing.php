<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('dashboard_homepage', new Route('/', array(
    '_controller' => 'DashboardBundle:Default:index',
)));

return $collection;
