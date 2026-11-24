<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blogs - CMNHousing Admin Portal</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
  <style>
    .blog-thumb { width:56px; height:56px; object-fit:cover; border-radius:8px; background:#e2e8f0; display:block; }
    .blog-actions { display:flex; gap:8px; justify-content:flex-end; }
    .btn-edit { background:#f59e0b; color:#fff; border:0; border-radius:8px; width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; }
    .btn-delete { background:#ef4444; color:#fff; border:0; border-radius:8px; width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }
    .status-active { color:#16a34a; font-weight:700; }
    .status-inactive { color:#94a3b8; font-weight:700; }
    .blog-toolbar { display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
  </style>
</head>
<body>
  <div class="app-container">
    @include('layouts.navigation')
    <main class="page-content">
      <div class="page-header-actions">
        <div>
          <h1 class="page-title">Blogs</h1>
          <p class="page-subtitle">Create and manage blog posts shown on the site</p>
        </div>
        <a class="btn-primary" href="{{ route('blogs.create') }}" style="background:#16a34a;">Add Blog</a>
      </div>

      @if (session('success'))
        <div class="card" role="status" style="padding:14px 18px; margin-bottom:16px; color:var(--accent-emerald);">{{ session('success') }}</div>
      @endif

      <div class="card" style="padding:20px;">
        <div class="blog-toolbar">
          <div style="color:var(--text-muted); font-size:14px;">Show {{ $blogs->perPage() }} entries</div>
          <form method="GET" action="{{ route('blogs.index') }}" style="display:flex; gap:8px; align-items:center;">
            <label for="blogSearch" style="font-size:14px; color:var(--text-secondary);">Search:</label>
            <input class="form-control" id="blogSearch" type="search" name="search" value="{{ $search }}" placeholder="Search blogs..." style="width:220px;">
          </form>
        </div>

        <div class="table-responsive">
          <table class="custom-table">
            <thead>
              <tr>
                <th>S.No.</th>
                <th>Title</th>
                <th>Image</th>
                <th>Status</th>
                <th style="text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($blogs as $index => $blog)
                <tr>
                  <td>{{ $blogs->firstItem() + $index }}</td>
                  <td>{{ $blog->title }}</td>
                  <td>
                    @if ($blog->imageUrl())
                      <img class="blog-thumb" src="{{ $blog->imageUrl() }}" alt="">
                    @else
                      <span style="color:var(--text-muted);">-</span>
                    @endif
                  </td>
                  <td>
                    <span class="{{ $blog->isActive() ? 'status-active' : 'status-inactive' }}">{{ $blog->status }}</span>
                  </td>
                  <td>
                    <div class="blog-actions">
                      <a class="btn-edit" href="{{ route('blogs.edit', $blog) }}" title="Edit" aria-label="Edit blog">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                      </a>
                      <form method="POST" action="{{ route('blogs.destroy', $blog) }}" data-confirm="Delete this blog post?" data-confirm-title="Delete blog" data-confirm-label="Delete">
                        @csrf
                        @method('DELETE')
                        <button class="btn-delete" type="submit" title="Delete" aria-label="Delete blog">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" style="text-align:center; padding:28px; color:var(--text-muted);">No blog posts found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('partials.pagination', ['paginator' => $blogs])
      </div>
    </main>
  </div>
  @include('partials.confirm-modal')
</body>
</html>
