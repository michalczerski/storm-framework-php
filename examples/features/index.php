<?php 

require ("../../storm.php");

//TODO
// request
// plik

//logowanie/ustawienia

//strefa klienta - zalogowanie sprawdzanie
//i18n
//url - kontrolery/ api funkcje
// url inline

//konfiguracja
//error(500), error(404); do widoku konfiguracji to jakos tak
//settings(function() {});
//_i18n("");

$app = storm\app();

$app->filter(function() {});
$app->filter("filter");


//routing on file - it can be function or class
//ob start
//ob end  to get function
// it there is no defined [action] by default it's index
$app->route("/", function ($request) {});
$app->route("/version", function ($request) {});
$app->route("/version-text", function ($request) {});
$app->route("/version-json", function ($request) {});
$app->route("api/[file]", "app");
$app->route("api/[file]/[action]", "app");
$app->route("api/product/[action]/{name}", "app"); 
$app->route("product/{name}/{id}", "app/controllers");
$app->route("[file]", "app/controllers");
$app->route("[file]/[action]", "app/controllers");

$app->run();

?>