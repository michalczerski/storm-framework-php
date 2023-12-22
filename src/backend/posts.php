<?php

use function storm\view;

function getIndex($req, $res) {
    return view('@view-backend/home.view');
}