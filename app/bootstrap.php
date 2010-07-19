<?php

// Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';


// Configure environment
Debug::enable();
Environment::loadConfig();


// Configure application
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
$application->catchExceptions = Environment::isProduction();


// Setup router
$router = $application->getRouter();

$router[] = new Route('index.php', array(
	'presenter'    => 'Default',
	'action'       => 'default',
), Route::ONE_WAY);

$router[] = new Route('<presenter>/<action>/<id>', array(
	'presenter'    => 'Default',
	'action'       => 'default',
	'id' => NULL,
));


//RoutingDebugger::enable();


// Run application!
$application->run();
