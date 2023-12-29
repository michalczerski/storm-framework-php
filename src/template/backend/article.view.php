{% layout backend %}

<div class="mt-5 h-full relative">
    <div class="btn-group flex justify-end absolute right-0 top-2">
        <button id="unpublish-btn" class="btn btn-l hidden">Unpublish</button>
        <button id="publish-btn" class="btn btn-l">Publish</button>
        <button id="save-btn" class="w-16 btn btn-r">
            <div class="flex items-center">
                <div id="save-caption">Save</div>
                <div id="save-in-progress" class="hidden">
                    <svg width="24" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle class="spinner_S1WN" cx="4" cy="12" r="3"/>
                        <circle class="spinner_S1WN spinner_Km9P" cx="12" cy="12" r="3"/>
                        <circle class="spinner_S1WN spinner_JApP" cx="20" cy="12" r="3"/>
                    </svg>
                </div>
            </div>
        </button>
    </div>
    <div id="editable"></div>
</div>

<script>
    const editor = new MediumEditor('#editable', {
        placeholder: false,
        extensions: {
            'multi_placeholder': new MediumEditorMultiPlaceholders({
                placeholders: [{
                        tag: 'h3',
                        text: 'Article title'
                    }, {
                        tag: 'p',
                        text: 'Write your article here...'
                    }
                ]
            })
        }
    });

    let articleIsSaved = articleId = null;
    let isPublished = false;
    let editedTitle = "";
    let editedContent = "";
    const saveInProgress = document.getElementById('save-in-progress');
    const saveCaption = document.getElementById('save-caption');
    const publishBtn = document.getElementById('publish-btn');
    const unpublishBtn = document.getElementById('unpublish-btn');
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
                content: editedContent
            })
        }).then(async (res) => {
            articleId = await res.text();
            articleIsSaved = true;
        }).finally(() => {
            toggleUI();
            saveInProgress.style.display = "none";
            saveCaption.style.display = "block";
        })

        publishBtn.onclick = async () => {
            await fetch(`/admin/articles/publish?article-id=${articleId}`)
                .then(async (res) => {
                    isPublished = true;
                })
                .finally(() => {
                    toggleUI();
                })
        }

        unpublishBtn.onclick = async () => {
            await fetch(`/admin/articles/unpublish?article-id=${articleId}`)
                .then(async (res) => {
                    isPublished = false;
                })
                .finally(() => {
                    toggleUI();
                })
        }
    }

    editor.subscribe('editableInput', function (event, editable) {
        toggleUI();
    });

    toggleUI();

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
</script>