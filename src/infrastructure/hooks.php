<?php

require(__DIR__ . "/vendor/autoload.php");

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$addDatabaseHook = function($request, $response, $di) {
    $settings = $di->settings->mongo;
    $client = new Client($settings);
    $di['db'] = $client->blog;
};

$addUserHook = function($request, $response, $di) {
    $di['user'] = $user = ['authenticated' => false];
    if ($request->hasCookie('sid')) {
        $sid = $request->getCookie('sid');
        $sid = new ObjectId($sid);
        $session = $di['db']->sessions->findOne(['_id' => $sid]);
        if (!$session) return;

        $validation = $session->validation->toDateTime();

        $now = new DateTime();
        if ($validation > $now) {
            $now->modify('+15 minutes');
            $validation = new UTCDateTime($now);

            $di['db']->sessions->updateOne(
                ['_id' => $sid],
                ['$set' => ['validation' =>  $validation]]);
            $user['authenticated'] = true;
            $user['id'] = $session['userid'];
            $user['name'] = $session['username'];
        } else {
            $response->removeCookie('sid');
        }
    }
    $di['user'] = $user;
};

$addAuthenticationHook = function($request, $response, $di) {
    $user = $di['user'];
    $isAdmin = str_starts_with($request->uri, "/admin");
    if ($isAdmin && $request->uri != '/admin/login' && !$user['authenticated']) {
        $response->redirect('/admin/login');
    }
};