<?php

use function storm\getFromDi;
use MongoDB\BSON\UTCDateTime;
function signInUser($username, $password, $remeberMe = false) {
    $password = hashPassword($password);
    $db = getFromDi('db');
    $editor = $db->users->findOne(['name' => $username, 'password' => $password]);
    if ($editor == null) return false;

    $validationTimeSpan = $remeberMe ? "+6 months" : "+15 minutes";
    $now = new DateTime();
    $validation = $now->modify($validationTimeSpan);
    $validation= new UTCDateTime($validation);
    $session =
        ['userid' => $editor->_id,
        'username' => $editor["name"],
        'validation' => $validation];
    $result = $db->sessions->insertOne($session);

    return $result->getInsertedId();
}

function signOut($sessionId) {
    $db = getFromDi('db');
}

function hashPassword(string $password) : string {
    $hashPassword = hash('sha256', $password);
    return $hashPassword;
}
