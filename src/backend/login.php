<?php

    use function storm\import;
    use function storm\page;

    import("@service.backend/editor.service");

    function getIndex() {
        $user = signInUser("test", "test");
        print_r($user);
        return page('@view-backend/login.view');
    }

    function postIndex($request) {
        return page('@view-backend/login.view', ['message' => "User doesn't exists"] );
    }


