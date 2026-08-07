<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'new_enquiries' => ContactMessage::query()->unhandled()->count(),
                'products' => Product::query()->active()->count(),
                'projects' => Project::query()->active()->count(),
                'posts' => Post::query()->published()->count(),
            ],
            // The inbox is the reason most people open this panel, so the ten
            // most recent enquiries are on the dashboard rather than one click away.
            'recent' => ContactMessage::query()
                ->with('product')
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
