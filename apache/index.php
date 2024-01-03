<?php

require("../storm.php");
require('../src/infrastructure/hooks.php');

global $addDatabaseHook, $addUserHook, $addAuthenticationHook, $addI18n;

$app = app('../src');

$app->directories([
    '@components-backend' => 'backend/components',
    '@view-backend' => "template/backend",
    '@finders-backend' => 'backend/finders',
    '@view-frontend' => "template/frontend"
]);

$app->view->helpers('@/template/helpers.php');
$app->view->layouts(['backend' => '@view-backend/layout.view.php']);

$app->settings("settings.$app->env.json");

$app->hook('before', $addI18n);
$app->hook('before', $addDatabaseHook);
$app->hook('before', $addUserHook);
$app->hook('before', $addAuthenticationHook);

$app->route("/php", function() { phpinfo(); });

$app->route("/admin", "backend/articles");
$app->route("/admin/[file]", "backend/[file]");
$app->route("/admin/[file]/[action]", "backend/[file]");

$app->route("/", "frontend/home");
$app->route("/[file]", "frontend/[file]");

$app->run();