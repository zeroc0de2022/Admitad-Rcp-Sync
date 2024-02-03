<?php
/*
Date: 11.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);


use Cpsync\Route\ApiPage;
use Cpsync\Route\LoginPage;
use Cpsync\Route\ImporterPage;
use Cpsync\Route\MainPage;
use Cpsync\Route\NotFoundPage;
use Cpsync\Route\ParserPage;
use Cpsync\Route\ToolPage;
use Cpsync\Route\ProfilePage;
use Cpsync\Route\ProxyPage;
use Cpsync\Route\RegisterPage;
use Cpsync\Route\UsersPage;
use Cpsync\Route\VerifyPage;

return [
    '/'                        => [MainPage::class ,    'handleRequest'],
    '/api'                     => [ApiPage::class,      'handleRequest'],
    '/api/\?{api_key}'         => [ApiPage::class,      'handleRequest'],
    '/importers'               => [ImporterPage::class, 'handleRequest'],
    '/importers/{tool_name}'   => [ImporterPage::class, 'relocate'],
    '/tools/{tool_name}'       => [ToolPage::class,     'handleRequest'],
    '/tools'                   => [ToolPage::class,     'requestHandle'],
    '/proxy'                   => [ProxyPage::class,    'handleRequest'],
    '/profile'                 => [ProfilePage::class,  'handleRequest'],
    '/verify'                  => [VerifyPage::class,   'handleRequest'],
    '/login'                   => [LoginPage::class,    'handleRequest'],
    '/register'                => [RegisterPage::class, 'handleRequest'],
    '/users'                   => [UsersPage::class,    'handleRequest'],
    '/logout'                  => [LoginPage::class,    'logout'],
    '/pars/'                   => [ParserPage::class,   'handleRequest'],
    '/{url_key}'               => [NotFoundPage::class, 'handleRequest']
];