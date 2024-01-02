<html xmlns="http://www.w3.org/1999/html">
    <head>
        <title>Blog - login</title>
        <link href="/public/main.css" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="/public/storm-cms.ico">
    </head>

    <body class="d-flex align-items-center py-4 bg-body-tertiary">
        <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <img class="mx-auto  w-auto" src="/public/storm-cms.png" alt="Storm CMS">
                <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                    Sign in to your Storm CMS account
                </h2>
            </div>

            <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <form action="/admin/login" method="POST">
                    <div>
                        <label for="username" class="block text-sm font-medium leading-6 text-gray-900">Username</label>
                        <div class="mt-2">
                            <input id="username" name="username" type="text"
                                   required
                                   class="block w-full rounded-md border-0 py-1.5 pl-3 text-gray-900
                                    shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                                    focus:ring-inset focus:ring-blue-400">
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                                Password
                            </label>
                        </div>
                        <div class="">
                            <input id="password" name="password" type="password" required
                                   class="block w-full rounded-md border-0 py-1.5 pl-3 text-gray-900
                                    shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2
                                    focus:ring-inset focus:ring-blue-400">
                        </div>
                    </div>
                    @if ($message)
                    <div>{{ $message }}</div>
                    @endif
                    <div class="mt-12">
                        <button type="submit" class="btn w-full">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>