<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если интересно, то посмотрите DI Container
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
//  $app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$users = [];
for ($i = 1; $i < 11; $i++) {
    $users[] = "Name{$i}";
};
$list = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
$users = array_merge($users, $list);

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

$app->get('/users', function ($request, $response, $args) use ($users) {
    //$response->getBody()->write('GET /users');
    $term = $request->getQueryParam('term');
    $filteredUsers = $users;
    $params = ['users' => $filteredUsers, 'term'=> $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/courses/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get("/users/{id}", function ($request, $response, $args) {
    $id = $args["id"];
    $params =["id" => $id, "nickname" => "User - " . $id];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();