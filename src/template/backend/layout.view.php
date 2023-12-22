<?php
 $user = \storm\getFromDi('user');
 $username = $user['username'];
?>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>Blog</title>
    <link href="/public/main.css" rel="stylesheet">
</head>
<body class="font-light leading-none">
    <main class="flex flex-col w-full h-full relative">
        <a href="/admin">
            <img class="absolute p-5" src="/public/storm-cms.png" />
        </a>
        <div class="flex justify-between w-100 py-5 bg-white border-b-1 h-24">
            <a href="/admin" class="pl-2 ml-[140px] font-medium text-slate-400">
                Storm CMS
            </a>
            <a href="/admin/logout" class="pr-5 ">Wyloguj</a>
        </div>
        <div class="flex h-full">
            <div class="pl-7 pt-[100px] w-1/5 bg-white h-full">
                <ul class="">
                    <li class="nav-item">
                        <a href="#" >
                            Posts
                        </a>
                    </li>
                    <li class="mt-5 ">
                        <a href="#" >
                            User
                        </a>
                   </ul>
            </div>
            <div class="p-3 rounded-tl-lg bg-slate-50 w-full shadow-t">
                {% $content %}
            </div>
        </div>
    </main>
</body>
</html>