<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactMessage::class);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ContactMessage::STATUSES)],
            'type' => ['nullable', Rule::in(ContactMessage::TYPES)],
        ]);

        $enquiries = ContactMessage::query()
            ->with('product')
            ->ofStatus($validated['status'] ?? null)
            ->when(filled($validated['type'] ?? null), fn ($q) => $q->where('type', $validated['type']))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.enquiries.index', [
            'enquiries' => $enquiries,
            'activeStatus' => $validated['status'] ?? null,
            'activeType' => $validated['type'] ?? null,
        ]);
    }

    public function show(ContactMessage $enquiry): View
    {
        $this->authorize('view', $enquiry);

        // Opening an enquiry marks it read, but never downgrades a status
        // someone already moved forward.
        if ($enquiry->status === 'new') {
            $enquiry->forceFill(['status' => 'read'])->saveQuietly();
        }

        return view('admin.enquiries.show', [
            'enquiry' => $enquiry->load('product', 'handler'),
        ]);
    }

    public function update(Request $request, ContactMessage $enquiry): RedirectResponse
    {
        $this->authorize('update', $enquiry);

        $validated = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $enquiry->update([
            ...$validated,
            'handled_at' => now(),
            'handled_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.enquiries.show', $enquiry)
            ->with('status', __('admin.updated'));
    }

    public function destroy(ContactMessage $enquiry): RedirectResponse
    {
        $this->authorize('delete', $enquiry);

        $enquiry->delete();

        return redirect()
            ->route('admin.enquiries.index')
            ->with('status', __('admin.deleted'));
    }
}
