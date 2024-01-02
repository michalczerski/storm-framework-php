@layout backend

<div>
    <div class="btn-group flex justify-start">
        <button id="unpublish-btn" class="btn btn-l hidden" onclick="unpublish()">
            Unpublish
        </button>
        <button id="publish-btn" class="btn btn-l" onclick="publish()">
            Publish
        </button>
        <button id="save-btn" class="w-16 btn btn-r">
            <div class="flex items-center">
                <div id="save-caption">Save</div>
                <div id="save-in-progress" class="hidden">
                    <svg width="24" height="20" viewBox="0 0 24 24"
                         xmlns="http://www.w3.org/2000/svg">
                        <circle class="spinner_S1WN" cx="4" cy="12" r="3"/>
                        <circle class="spinner_S1WN spinner_Km9P" cx="12" cy="12" r="3"/>
                        <circle class="spinner_S1WN spinner_JApP" cx="20" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
        </button>
    </div>
    <div id="editable" class="font-mono mt-7">{{ $article?->content }}</div>
</div>

@if($article)
<script>
    let articleIsSaved = true;
    let articleId = '{{ $article->_id }}';
    let isPublished = {{ $article['isPublished'] ? 1 : 0 }};
</script>
@else
<script>
    let articleIsSaved = false;
    let articleId = null;
    let isPublished = false;
</script>
@endif
<script>
    let extensions = {};

    if (!articleIsSaved) {
        extensions['multi_placeholder'] = new MediumEditorMultiPlaceholders({
            placeholders: [{
                tag: 'h3',
                text: 'Article title'
            }, {
                tag: 'p',
                text: 'Write your article here...'
            }]
        });
    }

    const editor = new MediumEditor('#editable', {
        placeholder: false,
        spellcheck: false,
        extensions: extensions
    });

    let editedTitle = "";
    let editedContent = "";
    const saveInProgress = document.getElementById('save-in-progress');
    const saveCaption = document.getElementById('save-caption');
    const unpublishBtn = document.getElementById('unpublish-btn');
    const publishBtn = document.getElementById('publish-btn');
    const saveBtn = document.getElementById('save-btn');
    saveBtn.onclick = async function () {
        saveInProgress.style.display = "block";
        saveCaption.style.display = "none"
        await fetch("/admin/articles/save", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                pid: articleId,
                title: editedTitle,
                content: editor.getContent()
            })
        }).then(async (res) => {
            if (!articleIsSaved) {
                articleId = await res.text();
                articleIsSaved = true;
            }
        }).finally(() => {
            toggleUI();
            saveInProgress.style.display = "none";
            saveCaption.style.display = "block";
        })
    }

    function toggleUI() {
        let articleIsNotEmpty = false

        const article = editor.getContent();
        const element = document.createElement('div');
        element.innerHTML = article;

        if (element.childNodes.length >= 2) {
            editedTitle = element.children.item(0).textContent;
            editedContent = element.children.item(1).textContent;
            articleIsNotEmpty = editedTitle.length > 0 && editedContent.length > 0;
        }

        if (articleIsNotEmpty) {
            saveBtn.removeAttribute("disabled");
        } else {
            saveBtn.setAttribute("disabled", true);
            publishBtn.setAttribute("disabled", true);
        }

        if (articleIsSaved && articleIsNotEmpty) {
            publishBtn.removeAttribute("disabled");
        }

        if (!isPublished && articleIsSaved && articleIsNotEmpty) {
            publishBtn.style.display = "block";
            unpublishBtn.style.display = "none";
        }

        if (isPublished && articleIsSaved && articleIsNotEmpty) {
            publishBtn.style.display = "none"
            unpublishBtn.style.display = "block"
        }
    }

    async function publish() {
        await fetch(`/admin/articles/publish?article-id=${articleId}`)
            .then(async (res) => {
                isPublished = true;
            })
            .finally(() => {
                toggleUI();
            })
    }

    async function unpublish() {
        await fetch(`/admin/articles/unpublish?article-id=${articleId}`)
            .then(async (res) => {
                isPublished = false;
            })
            .finally(() => {
                toggleUI();
            })
    }

    editor.subscribe('editableInput', toggleUI);

    toggleUI();
</script>