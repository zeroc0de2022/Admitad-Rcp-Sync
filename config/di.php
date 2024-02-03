<?php
/*
Date: 27.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

use Cpsync\Database;
use Cpsync\Mapper\Login;
use Cpsync\Mapper\Proxy;
use Cpsync\Mapper\Tool;
use Cpsync\Mapper\User;
use Cpsync\Session;
use Cpsync\Twig\AssetExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function Di\autowire;
use function DI\get;

return [

	'server.params' => $_SERVER, // $loader = new FilesystemLoader('templates');
	FilesystemLoader::class => autowire()->constructorParameter('paths', 'templates'), // $view = new Environment($loader);
	Environment::class => autowire()->constructorParameter('loader', get(FilesystemLoader::class))->method('addExtension', get(AssetExtension::class)),

	Database::class => autowire()->constructorParameter('connection', get(PDO::class)),

	PDO::class => autowire()->constructor(getenv('DATABASE_DSN'), getenv('DATABASE_USERNAME'), getenv('DATABASE_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false,])->method('exec', "set names utf8"),

	AssetExtension::class => autowire()->constructorParameter('serverParams', get('server.params')),

	Session::class => autowire(),

	Proxy::class => autowire()->constructor(get(Database::class)),

	Tool::class => autowire()->constructor(get(Database::class)),

	User::class => autowire()->constructor(get(Database::class), get(Session::class)),

	Login::class => autowire()->constructor(get(Database::class), get(Session::class)),


];
