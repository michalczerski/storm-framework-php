<?php

use function storm\import;
use MongoDB\Client;

import ('@/infrastructure/vendor/autoload');

function loadByCredentials($username, $password) {
    $client = new MongoDB\Client('mongodb://localhost:21017');
}