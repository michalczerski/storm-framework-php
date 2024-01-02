<?php

use function storm\import;

import("@services.backend/editor.service");

function index($req, $res, $di) {
    $sid = $req->getCookie('sid');
    $res->removeCookie('sid');
    signOut($sid);

    $res->redirect("/");
}