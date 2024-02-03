<?php
declare(strict_types = 1);
/***
 * Date 01.05.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

use Cpsync\Session;
use DI\ContainerBuilder;
use DI\NotFoundException;
use Slim\Factory\AppFactory;
use DevCoder\DotEnv;

require __DIR__.'/vendor/autoload.php';
// Loading environment variables
(new DotEnv(__DIR__.'/.env'))->load();
try {
	// Create new container builder DI
	$builder = new ContainerBuilder();
	// Passing dependency settings to the container
	$builder->addDefinitions('config/di.php');
	// Create new container
	$container = $builder->build();
	// For Slim to see and Use the container to create new objects
	AppFactory::setContainer($container);
	$app = AppFactory::create();
	$app->addBodyParsingMiddleware(); // middleware for POST
	$app->add(Session::class.':sessionInit');

	// Defining handlers
	$handlers = require __DIR__.'/config/handlers.php';
	foreach($handlers as $route => $handler) {
		if(is_array($handler)) {
			[$class, $method] = $handler;
			$app->any($route, $class.':'.$method);
		} else {
			$app->get($route, $handler);
		}
	}
	$app->run();
} catch(InvalidArgumentException $exception) {
	throw new InvalidArgumentException(__LINE__.': '.$exception->getMessage());
} catch(Exception|NotFoundException $exception) {
	header('Location: /');
}














