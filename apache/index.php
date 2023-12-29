<?php

require("../storm.php");
require('../src/infrastructure/hooks.php');

global $addDatabaseHook;
global $addUserHook;
global $addAuthenticationHook;

$app = storm\app('../src');

$app->directories([
    '@services.backend' => 'backend/services',
    '@view-backend' => "template/backend",
    '@finders-backend' => 'backend/finders',

    '@view-frontend' => "template/frontend"
]);

$app->view->layouts(['backend' => '@view-backend/layout.view.php']);

$app->settings("settings.$app->env.json");

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