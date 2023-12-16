<?php

use function storm\import;
use MongoDB\Client;


import ('../src/app/infrastructure/vendor/autoload');

function loadByCredentials($username, $password) {
    $client = new MongoDB\Client('mongodb://localhost:mongo');
}