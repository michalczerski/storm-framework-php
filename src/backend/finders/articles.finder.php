<?php

use function storm\__;
use MongoDB\BSON\ObjectId;

function getArticles() {
    $db = __('db');
    $fields = ['content' => 0];
    $articles = $db->articles->find([], ['projection' => $fields])->toArray();
    foreach($articles as $article) {
        $article->id = $article->_id->__toString();
        if ($article->publishedAt != null) {
            $article->publishedAt = $article->publishedAt->toDateTime();
        }
    }
    return $articles;
}

function getArticle($articleId) {
    $db = __('db');
    return $db->articles->findOne(['_id' => new ObjectId($articleId)]);
}