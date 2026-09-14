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
            /*
             * Two silent failures, surfaced where somebody will see them.
             *
             * `MAIL_MAILER=log` is the shipped default and writes every
             * notification into a log file while reporting success, so an
             * enquiry nobody is told about looks exactly like one that was
             * delivered. The SMS panel has the same shape of problem: switched
             * off, or on with no number, sends nothing and says nothing.
             *
             * Neither is an error the application can fix for itself, and both
             * are invisible until a customer asks why nobody called back.
             */
            'mailIsGoingToTheLog' => config('mail.default') === 'log',
            'smsIsSilent' => ! \App\Support\Sms::enabled() || \App\Support\Sms::salesMobile() === '',

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
