<?php

use function storm\import;

import("@services.backend/editor.service");

function getIndex($req, $res, $di) {
    $sid = $req->getCookie('sid');
    $res->removeCookie('sid');
    signOut($sid);

    $res->redirect("/");
}