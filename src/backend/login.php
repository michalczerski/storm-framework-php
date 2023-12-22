<?php

    use function storm\import;
    use function storm\view;

    import("@services.backend/editor.service");

    function getIndex($req, $res, $di) {
        return view('@view-backend/login.view');
    }

    function postIndex($request, $response) {
        $username = $request->parameters['username'];
        $password = $request->parameters['password'];
        $session = signInUser($username, $password);
        if ($session) {
            $response->setCookie('sid', $session);
            $response->redirect("/admin");
        }

        $message = "Password is incorrect or user doesn't exist";

        return view('@view-backend/login.view', ['message' => $message] );
    }


