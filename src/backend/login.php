<?php

    use function storm\import;
    use function storm\view;

    import("@services.backend/editor.service");

    function index($request, $response) {
        $data = ['message' => null];
        if ($request->isPost()) {
            $username = $request->parameters['username'];
            $password = $request->parameters['password'];
            $session = signInUser($username, $password);
            if ($session) {
                $response->setCookie('sid', $session);
                $response->redirect("/admin");
            }

            $data['message'] = "Password is incorrect or user doesn't exist";
        }

        return view('@view-backend/login.view', $data);
    }


