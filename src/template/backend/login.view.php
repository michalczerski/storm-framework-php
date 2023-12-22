<html xmlns="http://www.w3.org/1999/html">
    <head>
        <title>Blog - login</title>
        <link href="/public/style.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" ></script>
    </head>

    <body class="d-flex align-items-center py-4 bg-body-tertiary">
        <main class="form-signin w-100 m-auto">
            <form method="post">
                <h1 class="h3 mb-3 fw-normal">Please sign in</h1>
                <div class="form-floating">
                    <input class="form-control" type="text" id="username" name="username" placeholder="username" />
                    <label for="username">Username</label>
                </div>
                <div class="form-floating">
                    <input class="form-control" type="password" id="password" name="password" placeholder="password"/>
                    <label for="password">Password</label>
                </div>
                <div>{{ $message }}</div>
                <button type="submit" class="btn btn-primary w-100 my-4" >Sign in</button>
            </form>
        </main>
    </body>
</html>