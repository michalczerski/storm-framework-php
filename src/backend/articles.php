<?php

use function storm\import;
use function storm\view;

import("@services.backend/*");
import("@finders-backend/*");

function getIndex($req, $res) {
    $articles = getArticles();
    return view('@view-backend/articles.view', ['articles' => $articles]);
}

function getEdit() {
    return view('@view-backend/article.view');
}

function postSave($req, $res) {
    $post = $req->body;
    $postId = addArticle($post);

    return $postId;
}

function getPublish($req) {
    publish($req->parameters['article-id']);
}

function getUnpublish($req) {
    unpublish($req->parameters['article-id']);
}