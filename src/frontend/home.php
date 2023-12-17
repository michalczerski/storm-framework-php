<?php

use function storm\page;

function getIndex() {
    return page("@view-frontend/home.view");
}