<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes) {
    // Préflight CORS : le navigateur envoie OPTIONS avant chaque requête
    // cross-origin non "simple" (JSON, Authorization...). CI4 ne génère pas
    // cette route automatiquement, il faut la déclarer explicitement pour
    // que le filtre "cors" (global) puisse répondre avec les bons headers.
    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });

    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');

    $routes->get('me', 'AuthController::me', ['filter' => 'jwt']);

    $routes->get('medecins', 'MedecinController::search', ['filter' => 'jwt']);
    $routes->get('medecins/(:num)/creneaux', 'MedecinController::creneaux/$1', ['filter' => 'jwt']);
    $routes->get('specialites', 'SpecialiteController::index');

    $routes->post('rendez-vous', 'RendezVousController::create', ['filter' => 'jwt']);
    $routes->get('rendez-vous', 'RendezVousController::index', ['filter' => 'jwt']);
    $routes->delete('rendez-vous/(:num)', 'RendezVousController::delete/$1', ['filter' => 'jwt']);

    $routes->group('admin', ['filter' => ['jwt', 'admin']], static function (RouteCollection $routes) {
        $routes->post('medecins', 'MedecinController::create');
        $routes->patch('medecins/(:num)', 'MedecinController::update/$1');
        $routes->delete('medecins/(:num)', 'MedecinController::delete/$1');

        $routes->post('specialites', 'SpecialiteController::create');
        $routes->patch('specialites/(:num)', 'SpecialiteController::update/$1');
        $routes->delete('specialites/(:num)', 'SpecialiteController::delete/$1');

        $routes->get('rendez-vous', 'RendezVousController::adminIndex');

        $routes->get('disponibilites', 'DisponibiliteController::index');
        $routes->post('disponibilites', 'DisponibiliteController::create');
        $routes->delete('disponibilites/(:num)', 'DisponibiliteController::delete/$1');
    });
});
