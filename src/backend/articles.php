<?php

import("@components-backend/*");

function index($req, $res) {
    $articles = getArticles();
    return view('@view-backend/articles.view', ['articles' => $articles]);
}

function edit($req, $res) {
    $data = ['article' => null];
    if ($req->parameters->exist('article-id')) {
        $articleId = $req->parameters['article-id'];
        $data['article'] = getArticle($articleId);
    }
    return view('@view-backend/article.view', $data);
}

function save($req, $res) {
    $post = $req->body;
    $postId = addArticle($post);

    return $postId;
}

function publish($req) {
    setPublishStatus($req->parameters['article-id'], true);
}

function unpublish($req) {
    setPublishStatus($req->parameters['article-id'], false);
}