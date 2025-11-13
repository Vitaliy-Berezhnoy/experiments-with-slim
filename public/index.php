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
    //$response->getBody()->write('Welcome to Slim!');
    //return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
    return $this->get('renderer')->render($response, 'home.phtml', []);
});

$app->post('/users', function ($request, $response) {
    //$response->getBody()->write('POST/users');

    // Извлекаем данные формы
    $user = $request->getParsedBodyParam('user', []);

    // Валидация
    $errors = [];
    if (empty($user['nickname'])) {
        $errors['nickname'] = 'Поле Никнейм не может быть пустым';
    }
    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный формат email';
    }

    // Если есть ошибки — показываем форму с сообщениями
    if (!empty($errors)) {
        $params = ['user' => $user, 'errors' => $errors];
        return $this->get('renderer')->render($response, 'users/new.phtml', $params);
    }

    // Генерируем ID
    $user['id'] = uniqid();

    // Сохраняем в файл (в формате JSON)
    $usersFilePath = __DIR__ . '/data/users.json';
    $users = [];

    // Читаем существующие данные
    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true) ?? [];
    }

    // Добавляем нового пользователя
    $users[] = $user;

    // Записываем обновлённый список
    file_put_contents($usersFilePath, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $response->withRedirect('/users', 302);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => ''
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users', function ($request, $response) {
    //$response->getBody()->write('GET /users');

    $usersFilePath = __DIR__ . '/data/users.json';
    $users = [];

    // Читаем существующие данные
    if (file_exists($usersFilePath)) {
        $users = json_decode(file_get_contents($usersFilePath), true) ?? [];
    }

    $term = $request->getQueryParam('term','');
    if ($term === '') {
        $filteredUsers = $users;
    } else {
        $filteredUsers = [];
        foreach ($users as $user) {
            if (str_contains($user['nickname'], $term)) {
                $filteredUsers[] = $user;
            };
        };
    };
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $offset = ($page -1) * $per;
    $slice = array_slice($filteredUsers, $offset, $per);
    //$response->getBody()->write(json_encode($slice));
    // Надо добавить в шаблон кнопки для листания страниц!

    $params = ['users' => $slice, 'term'=> $term];
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