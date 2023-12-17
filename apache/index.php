<?php

require("../storm.php");

$app = storm\app("../src");

$app->directories([
    '@service.backend' => 'backend/services',
    '@repository.backend' => 'backend/repositories',
    '@view-backend' => "backend/views",

    '@view-frontend' => "frontend/views"
]);

$app->settings("settings.$app->env.json");

$app->route("/php", function() { phpinfo(); });

$app->route("/admin", "backend/home");
$app->route("/admin/[file]", "backend/[file]");

$app->route("/", "frontend/home");
$app->route("/[file]", "frontend/[file]");

$app->run();