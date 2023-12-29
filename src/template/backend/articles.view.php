{% layout backend %}

<div class="flex justify-end">
    <a class="btn" href="/admin/articles/edit">Add article</a>
</div>
<?php foreach($articles as $article): ?>

<div>
    <div><?php echo $article->title ?></div>
</div>

<?php endforeach; ?>
