<?php

use function storm\getFromDi;

function getArticles() {
    $db = getFromDi('db');
    return $db->articles->find()->toArray();
}