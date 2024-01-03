<?php

import("@components-backend/*");

function index($req, $res, $di) {
    $sid = $req->getCookie('sid');
    $res->removeCookie('sid');
    signOut($sid);

    $res->redirect("/");
}