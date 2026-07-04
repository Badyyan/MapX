<?php

namespace App\Http\Controllers;

use App\Jobs\PublishPost;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Post;
use App\Services\AiService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        return view('posts.index', [
            'posts' => Post::with('statuses')->latest()->paginate(10),
        ]);
    }

    public function create()
    {
        return view('posts.form', [
            'post' => new Post(),
            'branches' => Branch::orderBy('name')->get(),
            'platforms' => collect(config('mapx.platforms'))
                ->filter(fn ($p) => in_array('posts', $p['capabilities'], true)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'action' => ['required', 'in:draft,schedule,publish'],
            'ai_generated' => ['nullable', 'boolean'],
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'platforms' => $data['platforms'],
            'branch_ids' => array_map('intval', $data['branch_ids']),
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('posts', 'public') : null,
            'cta_url' => $data['cta_url'] ?? null,
            'ai_generated' => $request->boolean('ai_generated'),
            'status' => match ($data['action']) {
                'publish' => 'publishing',
                'schedule' => 'scheduled',
                default => 'draft',
            },
            'scheduled_at' => $data['action'] === 'schedule' ? $data['scheduled_at'] : null,
        ]);

        if ($data['action'] === 'publish') {
            PublishPost::dispatch($post);
        }

        AuditLog::record('post.created', $post, ['action' => $data['action']]);

        return redirect()->route('posts.index')->with('success', match ($data['action']) {
            'publish' => __('Post is being published.'),
            'schedule' => __('Post scheduled.'),
            default => __('Draft saved.'),
        });
    }

    public function show(Post $post)
    {
        $post->load('statuses.branch');

        return view('posts.show', compact('post'));
    }

    public function publish(Post $post)
    {
        abort_if(in_array($post->status, ['publishing', 'published'], true), 400);

        $post->update(['status' => 'publishing']);
        PublishPost::dispatch($post);

        return back()->with('success', __('Post is being published.'));
    }

    public function destroy(Post $post)
    {
        $post->delete();
        AuditLog::record('post.deleted', $post);

        return redirect()->route('posts.index')->with('success', __('Post deleted.'));
    }

    /** FR-22: AI post draft endpoint (JSON). */
    public function draft(Request $request, AiService $ai)
    {
        $request->validate(['topic' => ['required', 'string', 'max:500']]);

        return response()->json([
            'draft' => $ai->generatePostDraft(
                $request->topic,
                app()->getLocale(),
                $request->user()->company->name,
            ),
        ]);
    }
}
