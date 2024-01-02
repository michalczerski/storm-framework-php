<?php
 $user = \storm\__('user');
 $username = $user['name'];
?>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>Storm CMS</title>
    <link rel="icon" type="image/x-icon" href="/public/storm-cms.ico">

    <script src="/public/medium-editor.min.js"></script>
    <script src="/public/medium-editor-multi-placeholder.min.js"></script>
    <link rel="stylesheet" href="/public/medium-editor.min.css" />
    <link rel="stylesheet" href="/public/medium-editor.theme.css" />

    <link href="/public/main.css" rel="stylesheet">
</head>
<body class="leading-none">
    <main class="flex flex-col w-full h-full">
        <nav class="bg-white border-b h-16 text-sm font-medium text-slate-500">
            <div class="container flex justify-between mx-auto h-full relative items-center">
                <a href="/admin">
                    <img class="absolute top-0 mt-2 h-16" src="/public/storm-cms.png" />
                </a>
                <div class="ml-[90px] text-slate-500 flex h-full box-border">
                    <a href="/admin" class="mr-7 text-slate-400 flex items-center">Storm CMS</a>
                    <div class="flex items-center pt-[1px] border-b border-b-blue-400 mr-3 text-slate-800">
                        <a href="/admin/articles" class="px-1 border-b-blue-300">Articles</a>
                    </div>
                    <div class="hover:text-slate-600 hover:pt-[1px] hover:border-b border-b-blue-300
                                flex items-center mr-3">
                        <a href="" class="px-1">Users</a>
                    </div>
                </div>
                <div class="grow flex justify-end mr-5">
                    <span class="rounded-l-md border-l border-t border-b pt-[2px] pl-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </span>
                    <input type="text" class="border-r border-t border-b rounded-r-md p-1 pl-2" placeholder="Search" />
                </div>
                <div class="hover:text-slate-800 hover:pt-[1px] h-full
                                hover:border-b border-b-red-500
                                flex items-center">
                    <a href="/admin/logout">
                        {{ $username }}, signout
                    </a>
                </div>

            </div>
        </nav>
        <div class="container mx-auto pt-5 pl-2 mt-7">
            @template
        </div>
    </main>
</body>
</html>