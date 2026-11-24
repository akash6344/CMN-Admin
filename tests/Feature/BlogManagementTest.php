<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_blogs(): void
    {
        $this->get(route('blogs.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_blogs(): void
    {
        $user = User::factory()->create(['role' => 'seller', 'admin_role' => null]);

        $this->actingAs($user)
            ->get(route('blogs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_blogs_index(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        Blog::factory()->create(['title' => 'What is Common Man Housing?']);

        $this->actingAs($admin)
            ->get(route('blogs.index'))
            ->assertOk()
            ->assertSee('Blogs')
            ->assertSee('What is Common Man Housing?');
    }

    public function test_admin_can_create_a_blog(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['admin_role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('blogs.store'), [
                'title' => 'Market Update',
                'description' => 'A short market update for buyers.',
                'status' => 'Active',
                'image' => UploadedFile::fake()->image('news.jpg'),
            ])
            ->assertRedirect(route('blogs.index'));

        $this->assertDatabaseHas('blogs', [
            'title' => 'Market Update',
            'status' => 'Active',
        ]);

        $blog = Blog::query()->where('title', 'Market Update')->first();
        $this->assertNotNull($blog?->image_path);
        Storage::disk('public')->assertExists($blog->image_path);
    }

    public function test_admin_can_update_a_blog(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $blog = Blog::factory()->create([
            'title' => 'Old Title',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->put(route('blogs.update', $blog), [
                'title' => 'Updated Title',
                'description' => 'Updated body',
                'status' => 'Inactive',
            ])
            ->assertRedirect(route('blogs.index'));

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'title' => 'Updated Title',
            'status' => 'Inactive',
        ]);
    }

    public function test_admin_can_delete_a_blog(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['admin_role' => 'admin']);
        $path = UploadedFile::fake()->image('delete-me.jpg')->store('blogs', 'public');
        $blog = Blog::factory()->create([
            'title' => 'Delete Me',
            'image_path' => $path,
        ]);

        $this->actingAs($admin)
            ->delete(route('blogs.destroy', $blog))
            ->assertRedirect(route('blogs.index'));

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_upload_an_editor_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['admin_role' => 'admin']);

        $response = $this->actingAs($admin)
            ->postJson(route('blogs.editor-image'), [
                'image' => UploadedFile::fake()->image('inline.png'),
            ])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/blogs/content/', $url);
    }

    public function test_blog_form_includes_rich_text_editor(): void
    {
        $admin = User::factory()->create(['admin_role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('blogs.create'))
            ->assertOk()
            ->assertSee('blogEditorToolbar', false)
            ->assertSee('ql-bold', false)
            ->assertSee('ql-font', false);
    }
}
