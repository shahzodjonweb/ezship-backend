<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Load;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Only apply auth and admin middleware to specific methods
        $this->middleware('auth')->except(['showLoginForm', 'login']);
        $this->middleware('admin')->except(['showLoginForm', 'login']);
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $stats = [
            'total_orders' => Load::count(),
            'pending_orders' => Load::where('status', 'pending')->count(),
            'accepted_orders' => Load::where('status', 'accepted')->count(),
            'invoiced_orders' => Load::where('status', 'invoiced')->count(),
            'rejected_orders' => Load::where('status', 'rejected')->count(),
            'total_users' => User::count(),
            'total_revenue' => Load::where('status', 'invoiced')->sum('initial_price'),
        ];

        $recent_orders = Load::with(['user', 'categories', 'payment'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_orders'));
    }

    /**
     * Display a listing of all orders.
     *
     * @return \Illuminate\View\View
     */
    public function orders(Request $request)
    {
        $query = Load::with(['user', 'categories', 'payment']);

        // Filter by status if provided
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        $statuses = ['all', 'initial', 'pending', 'accepted', 'rejected', 'invoiced', 'completed', 'cancelled'];

        return view('admin.orders', compact('orders', 'statuses'));
    }

    /**
     * Show the form for editing an order.
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function editOrder($id)
    {
        $order = Load::with(['user', 'categories', 'stops.location', 'payment'])->findOrFail($id);
        $statuses = ['initial', 'pending', 'accepted', 'rejected', 'invoiced', 'completed', 'cancelled'];
        
        return view('admin.edit-order', compact('order', 'statuses'));
    }

    /**
     * Update the order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $order = Load::findOrFail($id);
        $oldStatus = $order->status;
        $order->status = $request->status;
        $order->save();

        // Log the status change
        DB::table('status_logs')->insert([
            'load_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'changed_by' => Auth::id(),
            'notes' => $request->notes,
            'created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order status updated successfully!');
    }

    /**
     * Update order details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateOrder(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
            'initial_price' => 'nullable|numeric|min:0',
            'counter_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $order = Load::findOrFail($id);
        
        $order->update([
            'status' => $request->status,
            'initial_price' => $request->initial_price,
            'counter_price' => $request->counter_price,
            'description' => $request->description,
            'phone' => $request->phone,
        ]);

        return redirect()->route('admin.orders')->with('success', 'Order updated successfully!');
    }

    /**
     * Delete an order.
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteOrder($id)
    {
        $order = Load::findOrFail($id);
        $order->delete();

        return redirect()->route('admin.orders')->with('success', 'Order deleted successfully!');
    }

    /**
     * Display order statistics and analytics.
     *
     * @return \Illuminate\View\View
     */
    public function analytics()
    {
        $statusCounts = Load::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $monthlyOrders = Load::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count'),
                DB::raw('sum(initial_price) as revenue')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $topCustomers = User::withCount('loads')
            ->with(['loads' => function($query) {
                $query->select('user_id', DB::raw('sum(initial_price) as total_spent'))
                    ->groupBy('user_id');
            }])
            ->orderBy('loads_count', 'desc')
            ->limit(10)
            ->get();

        return view('admin.analytics', compact('statusCounts', 'monthlyOrders', 'topCustomers'));
    }

    /**
     * Show the admin login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // If already logged in as admin, redirect to dashboard
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }

    /**
     * Handle admin login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Check if user is admin
            if (Auth::user()->is_admin) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            } else {
                Auth::logout();
                return back()->with('error', 'You do not have admin privileges.');
            }
        }

        return back()->with('error', 'Invalid credentials.');
    }
}