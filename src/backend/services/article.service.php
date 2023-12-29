<?php

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use function storm\getFromDi;

function addArticle($article) {
    $db = getFromDi('db');
    $user = getFromDi('user');

    $now = new UTCDateTime(new DateTime());

    if ($article->pid) {
        $data = [
            'title' =>  $article->title,
            'content' => $article->content,
            'lastUpdatedAt' => $now];
        $db->articles->updateOne(
            ['_id' => new ObjectId($article->pid)],
            ['$set' => $data]);
    } else {
        $data = [
            'title' => $article->title,
            'content' => $article->content,
            'isPublished' => false,
            'author' => ['username' => $user['name'], 'id' => $user['id']],
            'createdAt' => $now,
            'lastUpdatedAt' => null,
            'publishedAt' => null];

        $result = $db->articles->insertOne($data);

        return $result->getInsertedId();
    }
}

function publish($articleId) {
    setPublishStatus($articleId, true);
}

function unpublish($articleId) {
    setPublishStatus($articleId, false);
}

function setPublishStatus($articleId, $isPublished) {
    $data = ['isPublished' => $isPublished];
    if ($isPublished) {
        $now = new UTCDateTime(new DateTime());
        $data['publishedAt'] = $now;
    }
    $db = getFromDi('db');
    $db->articles->updateOne(
        ['_id' => new ObjectId($articleId)],
        ['$set' => $data]);
}

