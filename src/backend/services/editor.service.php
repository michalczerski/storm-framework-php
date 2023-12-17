<?php

use function storm\import;

import ('@repository.backend/editor.repository');

function signInUser($username, $password) {
    $password = hashPassword($password);
    $editor = loadByCredentials($username, $password);
    return $editor;
}

function hashPassword(string $password) : string {
    return $password;
}
