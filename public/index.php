<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$users = [];
for ($i = 1; $i < 11; $i++) {
    $users[] = "Name{$i}";
};

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$app->post('/users', function ($request, $response) use ($users) {
    $response->getBody()->write('POST/users');

    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 3);
    $offset = ($page -1) * $per;
    $slice = array_slice($users, $offset, $per);
    $response->getBody()->write(json_encode($slice));

    return $response->withStatus(302);
});

$app->get('/users', function ($request, $response) {
    return $response->write('GET /users');
});

$app->get('/courses/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->run();