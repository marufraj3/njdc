<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Services\ContentWorkflow;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function index(Request $request)
    {
        $query = ContentRevision::with(['revisionable', 'user']);
        if (! $request->user()->isPublisher()) {
            $query->where('user_id', $request->user()->id);
        }

        $action = $request->input('action');
        $action = is_string($action) && in_array($action, ['draft', 'submitted', 'published', 'rejected', 'superseded'], true) ? $action : null;
        $query->when(
            $action,
            fn ($builder, $action) => $builder->where('action', $action),
            fn ($builder) => $builder->where('is_working', true)->where(
                fn ($working) => $request->user()->isPublisher()
                    ? $working->where('action', 'submitted')
                    : $working->whereIn('action', ['draft', 'submitted', 'rejected'])
            )
        );

        $revisions = $query->latest()->paginate(30)->withQueryString();

        return view('admin.revisions.index', compact('revisions'));
    }

    public function show(Request $request, ContentRevision $revision)
    {
        $this->authorizeRevision($request, $revision, false);
        $revision->load(['revisionable', 'user']);

        return view('admin.revisions.show', compact('revision'));
    }

    public function submit(Request $request, ContentRevision $revision, ContentWorkflow $workflow)
    {
        $this->authorizeRevision($request, $revision);
        $workflow->submitRevision($revision, $request->user(), $request->validate(['note' => 'nullable|max:5000'])['note'] ?? null);

        return redirect()->route('admin.revisions.index')->with('status', 'সংশোধন অনুমোদনের জন্য পাঠানো হয়েছে।');
    }

    public function approve(Request $request, ContentRevision $revision, ContentWorkflow $workflow)
    {
        $workflow->publishRevision($revision, $request->user(), $request->validate(['note' => 'nullable|max:5000'])['note'] ?? null);

        return redirect()->route('admin.revisions.index')->with('status', 'সংশোধন অনুমোদন ও প্রকাশ করা হয়েছে।');
    }

    public function reject(Request $request, ContentRevision $revision, ContentWorkflow $workflow)
    {
        $note = $request->validate(['note' => 'required|max:5000'])['note'];
        $workflow->reject($revision, $request->user(), $note);

        return redirect()->route('admin.revisions.index')->with('status', 'সংশোধন ফেরত দেওয়া হয়েছে এবং কেবল এটির জন্য আপলোড করা মিডিয়া পরিষ্কার হয়েছে।');
    }

    public function destroy(Request $request, ContentRevision $revision, ContentWorkflow $workflow)
    {
        $this->authorizeRevision($request, $revision);
        $workflow->discardRevision($revision, $request->user());

        return redirect()->route('admin.revisions.index')->with('status', 'খসড়া সংশোধন ও এর অব্যবহৃত আপলোড মুছে ফেলা হয়েছে।');
    }

    private function authorizeRevision(Request $request, ContentRevision $revision, bool $workingOnly = true): void
    {
        abort_unless((! $workingOnly || $revision->is_working) && ($request->user()->isPublisher() || $revision->user_id === $request->user()->id), 403);
    }
}
