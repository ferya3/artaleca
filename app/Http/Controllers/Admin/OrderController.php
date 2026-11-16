<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Shop\Checkout;
use App\Shop\OrderNotifier;
use App\Shop\OrderStatus;
use App\Support\Mobile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'q' => ['nullable', 'string', 'max:80'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));

        $orders = Order::query()
            ->withCount('items')
            ->when(filled($validated['status'] ?? null), fn ($query) => $query->where('status', $validated['status']))
            ->when($q !== '', function ($query) use ($q) {
                $mobile = Mobile::normalize($q);

                $query->where(fn ($w) => $w
                    ->where('number', 'like', '%'.strtoupper($q).'%')
                    ->orWhere('customer_name', 'like', '%'.$q.'%')
                    ->orWhere('company', 'like', '%'.$q.'%')
                    ->when($mobile, fn ($m) => $m->orWhere('mobile', $mobile)));
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'activeStatus' => $validated['status'] ?? null,
            'search' => $q,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.show', ['order' => $order->load(['items', 'payments'])]);
    }

    /**
     * Move an order along, or leave a note on it.
     *
     * Only the next steps an order can actually take are accepted — a delivered
     * order does not go back to "awaiting payment" because somebody picked the
     * wrong line in a select. Each step does what it means: marking paid stamps
     * the date and tells the customer, cancelling returns the stock, shipping
     * sends the customer an SMS.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_map(fn (OrderStatus $s) => $s->value, $order->status->next()))],
            'staff_note' => ['nullable', 'string', 'max:4000'],
        ]);

        $to = filled($validated['status'] ?? null) ? OrderStatus::from($validated['status']) : null;

        DB::transaction(function () use ($order, $to, $validated) {
            $order->staff_note = $validated['staff_note'] ?? null;

            if ($to) {
                $order->status = $to;

                if ($to === OrderStatus::Paid && ! $order->paid_at) {
                    $order->paid_at = now();
                }

                if ($to === OrderStatus::Cancelled) {
                    $order->cancelled_at = now();
                    Checkout::release($order);
                }
            }

            $order->save();
        });

        match ($to) {
            OrderStatus::Paid => OrderNotifier::confirmedByStaff($order),
            OrderStatus::Shipped => OrderNotifier::shipped($order),
            default => null,
        };

        return redirect()->route('admin.orders.show', $order)->with('status', __('admin.updated'));
    }
}
