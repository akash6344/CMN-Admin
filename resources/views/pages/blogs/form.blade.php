<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $mode === 'edit' ? 'Edit Blog' : 'Add Blog' }} - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css">
  <style>
    .blog-form-card { max-width:820px; margin:0 auto; padding:28px; }
    .blog-form-card .form-group { margin-bottom:18px; }
    .blog-form-actions { display:flex; justify-content:flex-end; margin-top:20px; }
    .btn-go-back { border:1px solid rgb(0, 97, 77); color:rgb(0, 97, 77); background:#fff; padding:10px 18px; border-radius:8px; font-weight:600; text-decoration:none; }
    .current-image { width:120px; height:80px; object-fit:cover; border-radius:8px; margin-top:8px; display:block; }
    .field-error { display:block; margin-top:5px; color:var(--accent-red); }
    .ql-toolbar.ql-snow {
      border:1px solid #dbe3ee;
      border-radius:10px 10px 0 0;
      background:#f8fafc;
    }
    .ql-container.ql-snow {
      border:1px solid #dbe3ee;
      border-top:0;
      border-radius:0 0 10px 10px;
      min-height:220px;
      font-size:15px;
      background:#fff;
    }
    .ql-editor { min-height:220px; }
    .ql-snow .ql-stroke { stroke:#475569; }
    .ql-snow .ql-fill { fill:#475569; }
    .ql-snow .ql-picker { color:#475569; }
    .ql-snow .ql-picker.ql-header .ql-picker-label::before,
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value=""]::before { content: 'Paragraph'; }
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="1"]::before { content: 'Heading 1'; }
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]::before { content: 'Heading 2'; }
    .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]::before { content: 'Heading 3'; }
    .ql-font-serif { font-family: Georgia, 'Times New Roman', serif; }
    .ql-font-monospace { font-family: Menlo, Monaco, Consolas, monospace; }
    .ql-size-small { font-size: 0.75em; }
    .ql-size-large { font-size: 1.5em; }
    .ql-size-huge { font-size: 2.5em; }
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header">
        <h1 class="page-title">Blog</h1>
        <p class="page-subtitle">{{ $mode === 'edit' ? 'Update this blog post' : 'Create a new blog post' }}</p>
      </div>

      <div class="card blog-form-card">
        <form
          id="blogForm"
          method="POST"
          action="{{ $mode === 'edit' ? route('blogs.update', $blog) : route('blogs.store') }}"
          enctype="multipart/form-data"
        >
          @csrf
          @if ($mode === 'edit')
            @method('PUT')
          @endif

          <div class="form-group">
            <label class="form-label" for="blogTitle">Title</label>
            <input class="form-control" id="blogTitle" type="text" name="title" value="{{ old('title', $blog->title) }}" placeholder="Enter Your Title" maxlength="255" required>
            @error('title')<small class="field-error">{{ $message }}</small>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="blogImage">Image</label>
            <input class="form-control" id="blogImage" type="file" name="image" accept="image/jpeg,image/png,image/webp">
            @if ($blog->imageUrl())
              <img class="current-image" src="{{ $blog->imageUrl() }}" alt="Current blog image">
            @endif
            @error('image')<small class="field-error">{{ $message }}</small>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="blogDescription">Description</label>
            <div id="blogEditorToolbar">
              <span class="ql-formats">
                <select class="ql-header">
                  <option selected></option>
                  <option value="1"></option>
                  <option value="2"></option>
                  <option value="3"></option>
                </select>
                <select class="ql-size">
                  <option value="small"></option>
                  <option selected></option>
                  <option value="large"></option>
                  <option value="huge"></option>
                </select>
                <select class="ql-font"></select>
              </span>
              <span class="ql-formats">
                <button class="ql-bold" type="button"></button>
                <button class="ql-italic" type="button"></button>
                <button class="ql-underline" type="button"></button>
                <button class="ql-strike" type="button"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-link" type="button"></button>
                <button class="ql-list" value="bullet" type="button"></button>
                <button class="ql-list" value="ordered" type="button"></button>
                <button class="ql-image" type="button"></button>
                <button class="ql-blockquote" type="button"></button>
              </span>
              <span class="ql-formats">
                <button class="ql-clean" type="button"></button>
                <button class="ql-undo" type="button" aria-label="Undo">
                  <svg viewBox="0 0 18 18" width="18" height="18"><path class="ql-fill" d="M9 4.5A5.5 5.5 0 0 0 3.5 10H2l2.75 3L7.5 10H6A3.5 3.5 0 1 1 9.5 13.5h1.5A5 5 0 1 0 9 4.5z"></path></svg>
                </button>
                <button class="ql-redo" type="button" aria-label="Redo">
                  <svg viewBox="0 0 18 18" width="18" height="18"><path class="ql-fill" d="M9 4.5A5.5 5.5 0 0 1 14.5 10H16l-2.75 3L10.5 10H12A3.5 3.5 0 1 0 8.5 13.5H7A5 5 0 1 1 9 4.5z"></path></svg>
                </button>
              </span>
            </div>
            <div id="blogEditor"></div>
            <textarea id="blogDescription" name="description" hidden>{{ old('description', $blog->description) }}</textarea>
            @error('description')<small class="field-error">{{ $message }}</small>@enderror
          </div>

          <div class="form-group">
            <label class="form-label" for="blogStatus">Status</label>
            <select class="form-control" id="blogStatus" name="status" required>
              <option value="">Select</option>
              <option value="Active" @selected(old('status', $blog->status ?: 'Active') === 'Active')>Active</option>
              <option value="Inactive" @selected(old('status', $blog->status) === 'Inactive')>Inactive</option>
            </select>
            @error('status')<small class="field-error">{{ $message }}</small>@enderror
          </div>

          <div class="blog-form-actions">
            <button class="btn-primary" type="submit" style="background:rgb(0, 97, 77);">{{ $mode === 'edit' ? 'Save changes' : 'Create Blog' }}</button>
          </div>
        </form>
      </div>

      <div style="max-width:820px; margin:16px auto 0; display:flex; justify-content:flex-end;">
        <a class="btn-go-back" href="{{ route('blogs.index') }}">Go Back</a>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
  <script>
    const Font = Quill.import('formats/font');
    Font.whitelist = ['sans-serif', 'serif', 'monospace'];
    Quill.register(Font, true);

    const Size = Quill.import('formats/size');
    Size.whitelist = ['small', 'large', 'huge'];
    Quill.register(Size, true);

    const descriptionInput = document.getElementById('blogDescription');
    const editorImageUploadUrl = @json(route('blogs.editor-image'));
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function imageHandler() {
      const input = document.createElement('input');
      input.setAttribute('type', 'file');
      input.setAttribute('accept', 'image/jpeg,image/png,image/webp,image/gif');
      input.click();
      input.onchange = async () => {
        const file = input.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);

        const range = quill.getSelection(true);
        quill.insertText(range.index, 'Uploading image…', { italic: true }, 'user');

        try {
          const response = await fetch(editorImageUploadUrl, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json',
            },
            body: formData,
          });
          if (!response.ok) throw new Error('Upload failed');
          const data = await response.json();
          quill.deleteText(range.index, 'Uploading image…'.length);
          quill.insertEmbed(range.index, 'image', data.url, 'user');
          quill.setSelection(range.index + 1);
        } catch (error) {
          quill.deleteText(range.index, 'Uploading image…'.length);
          alert('Unable to upload image. Please try again.');
        }
      };
    }

    const quill = new Quill('#blogEditor', {
      theme: 'snow',
      placeholder: 'Write the blog content...',
      modules: {
        toolbar: {
          container: '#blogEditorToolbar',
          handlers: {
            image: imageHandler,
            undo() { quill.history.undo(); },
            redo() { quill.history.redo(); },
          },
        },
        history: { delay: 500, maxStack: 100, userOnly: true },
      },
    });

    const initialHtml = descriptionInput.value || '';
    if (initialHtml.trim() !== '') {
      quill.root.innerHTML = initialHtml;
    }

    document.getElementById('blogForm').addEventListener('submit', () => {
      const html = quill.root.innerHTML;
      descriptionInput.value = html === '<p><br></p>' ? '' : html;
    });
  </script>
</body>
</html>
