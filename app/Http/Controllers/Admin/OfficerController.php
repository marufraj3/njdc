<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Officer;
use App\Rules\PublicPathAvailable;
use App\Services\ContentWorkflow;
use App\Services\MediaManager;
use App\Support\HtmlSanitizer;
use App\Support\PublicPath;
use Illuminate\Http\Request;

class OfficerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->boolean('trashed')) abort_unless($request->user()->isPublisher(), 403);
        $officers = Officer::query()
            ->when($request->boolean('trashed'), fn ($query) => $query->onlyTrashed())
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('sort_order')->paginate(25)->withQueryString();

        return view('admin.officers.index', compact('officers'));
    }

    public function create()
    {
        return view('admin.officers.form', ['officer' => new Officer]);
    }

    public function store(Request $request, ContentWorkflow $workflow, MediaManager $media, HtmlSanitizer $sanitizer)
    {
        $data = $this->validated($request, $sanitizer);
        $newMediaId = null;
        if ($request->hasFile('photo')) {
            $newMediaId = $media->store($request->file('photo'), $request->user(), [
                'alt_bn' => $data['name_bn'],
                'alt_en' => $data['name_en'] ?? null,
            ])->id;
            $data['photo_media_id'] = $newMediaId;
        }
        try {
            $officer = $workflow->saveDraft(new Officer, $data, $request->user());
            $this->advance($officer, $request, $workflow);
        } catch (\Throwable $exception) {
            $workflow->cleanUnusedMedia($newMediaId);
            throw $exception;
        }

        return redirect()->route('admin.officers.index')->with('status', 'কর্মকর্তা সংরক্ষিত হয়েছে।');
    }

    public function edit(Officer $officer)
    {
        return view('admin.officers.form', compact('officer'));
    }

    public function update(Request $request, Officer $officer, ContentWorkflow $workflow, MediaManager $media, HtmlSanitizer $sanitizer)
    {
        $data = $this->validated($request, $sanitizer, $officer);
        $newMediaId = null;
        if ($request->boolean('remove_photo')) {
            $data['photo_media_id'] = null;
            $data['photo_url'] = null;
        }
        if ($request->hasFile('photo')) {
            $newMediaId = $media->store($request->file('photo'), $request->user(), [
                'alt_bn' => $data['name_bn'],
                'alt_en' => $data['name_en'] ?? null,
            ])->id;
            $data['photo_media_id'] = $newMediaId;
            $data['photo_url'] = null;
        }

        try {
            $result = $workflow->saveDraft($officer, $data, $request->user(), $request->input('note'));
            $this->advance($result, $request, $workflow);
        } catch (\Throwable $exception) {
            $workflow->cleanUnusedMedia($newMediaId);
            throw $exception;
        }

        return redirect()->route('admin.officers.index')->with(
            'status',
            $result instanceof ContentRevision ? 'সংশোধন অনুমোদনের জন্য সংরক্ষিত।' : 'হালনাগাদ হয়েছে।'
        );
    }

    public function submit(Request $request, Officer $officer, ContentWorkflow $workflow)
    {
        $workflow->submit($officer, $request->user());
        return back()->with('status', 'জমা হয়েছে।');
    }

    public function publish(Request $request, Officer $officer, ContentWorkflow $workflow)
    {
        $workflow->publish($officer, $request->user());
        return back()->with('status', 'প্রকাশিত হয়েছে।');
    }

    public function destroy(Officer $officer)
    {
        $officer->delete();
        return back()->with('status', 'কর্মকর্তা ট্র্যাশে পাঠানো হয়েছে; পুনরুদ্ধারের জন্য ছবিটি রাখা হয়েছে।');
    }

    public function restore(Request $request, int $officer, ContentWorkflow $workflow)
    {
        $record = Officer::onlyTrashed()->findOrFail($officer);
        $workflow->restoreOfficer($record, $request->user());

        return redirect()->route('admin.officers.index', ['trashed' => 1])->with('status', 'কর্মকর্তা পুনরুদ্ধার হয়েছে।');
    }

    public function forceDestroy(Request $request, int $officer, ContentWorkflow $workflow)
    {
        $record = Officer::onlyTrashed()->findOrFail($officer);
        $workflow->forceDeleteOfficer($record, $request->user());

        return redirect()->route('admin.officers.index', ['trashed' => 1])->with('status', 'কর্মকর্তা ও অব্যবহৃত ছবি স্থায়ীভাবে মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request, HtmlSanitizer $sanitizer, ?Officer $officer = null): array
    {
        $request->merge(['path' => PublicPath::normalize($request->input('path'))]);
        $data = $request->validate([
            'path' => ['required', 'max:512', new PublicPathAvailable('officers', $officer?->id)],
            'name_bn' => 'required|max:255',
            'name_en' => 'nullable|max:255',
            'designation_bn' => 'nullable|max:255',
            'designation_en' => 'nullable|max:255',
            'department_bn' => 'nullable|max:255',
            'department_en' => 'nullable|max:255',
            'office_bn' => 'nullable|max:255',
            'office_en' => 'nullable|max:255',
            'email' => 'nullable|email|max:255',
            'office_phone' => 'nullable|max:64',
            'mobile' => 'nullable|max:64',
            'intercom' => 'nullable|max:64',
            'room' => 'nullable|max:64',
            'fax' => 'nullable|max:64',
            'bio_bn' => 'nullable|max:100000',
            'bio_en' => 'nullable|max:100000',
            'sort_order' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:10240',
            'remove_photo' => 'nullable|boolean',
        ]);
        $data['bio_bn'] = $sanitizer->clean($data['bio_bn'] ?? '');
        $data['bio_en'] = $sanitizer->clean($data['bio_en'] ?? '');
        unset($data['photo'], $data['remove_photo']);

        return $data;
    }

    private function advance(Officer|ContentRevision $subject, Request $request, ContentWorkflow $workflow): void
    {
        if ($subject instanceof Officer && $subject->status === 'published') {
            return;
        }
        if ($request->intent === 'submit') {
            $subject instanceof ContentRevision
                ? $workflow->submitRevision($subject, $request->user())
                : $workflow->submit($subject, $request->user());
        }
        if ($request->intent === 'publish' && $subject instanceof Officer) {
            $workflow->publish($subject, $request->user());
        }
    }
}
