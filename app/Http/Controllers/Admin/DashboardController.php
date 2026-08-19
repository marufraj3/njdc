<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentItem;
use App\Models\ContentRevision;
use App\Models\MediaFile;
use App\Models\Officer;
use App\Models\Page;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'counts' => ['pages' => Page::count(), 'content' => ContentItem::count(), 'officers' => Officer::count(), 'media' => MediaFile::count()],
            'pending' => Page::where('status', 'pending_review')->count() + ContentItem::where('status', 'pending_review')->count() + Officer::where('status', 'pending_review')->count(),
            'revisions' => ContentRevision::where('is_working', true)->where('action', 'submitted')->latest()->limit(10)->get(),
        ]);
    }
}
