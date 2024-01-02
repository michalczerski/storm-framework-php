@layout backend

<div class="flex justify-end">
    <a class="btn" href="/admin/articles/edit">Add article</a>
</div>

<div class="mt-5">
    <table class="table-auto w-full">
        <tbody>
            @foreach($articles as $article)
            <tr class="hover:bg-slate-100">
                <td class="py-1 px-3">
                    <a href="{{ url('/admin/articles/edit', ['article-id' => $article->id]) }}">
                        {{ $article->title }}
                    </a>
                </td>
                <td>{{ $article->author->username }}</td>
                <td>
                    {{ $article->publishedAt | date }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>