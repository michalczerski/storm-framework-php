<?php

use function storm\page;

function getIndex() {
    return page('@view-backend/home.view');
}