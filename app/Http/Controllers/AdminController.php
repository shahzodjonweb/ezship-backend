<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Load;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\QuickBooksController;
use App\Http\Resources\Load as LoadResource;
use Illuminate\Support\Facades\Log;

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

        // Create QuickBooks invoice when status changes to accepted
        if ($request->status === 'accepted' && $oldStatus !== 'accepted') {
            try {
                $quickBooksController = new QuickBooksController();
                $result = $quickBooksController->createInvoice(new LoadResource($order));
                
                if (isset($result['status']) && $result['status'] === 'success') {
                    // Store QuickBooks invoice ID if needed
                    if (isset($result['invoice_id'])) {
                        $order->quickbooks_invoice_id = $result['invoice_id'];
                        $order->save();
                    }
                    Log::info('QuickBooks invoice created for load', [
                        'load_id' => $id,
                        'invoice_id' => $result['invoice_id'] ?? null
                    ]);
                } else {
                    Log::warning('Failed to create QuickBooks invoice', [
                        'load_id' => $id,
                        'result' => $result
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception creating QuickBooks invoice from admin', [
                    'load_id' => $id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the status update if invoice creation fails
            }
        }

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
            'initial_price' => 'nullable|numeric|min:0',
            'counter_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $order = Load::findOrFail($id);
        
        $order->update([
            'initial_price' => $request->initial_price,
            'counter_price' => $request->counter_price,
            'description' => $request->description,
            'phone' => $request->phone,
        ]);

        // Note: Status updates should go through updateOrderStatus method to trigger invoice creation
        // This method no longer handles status changes

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

    /**
     * Display a listing of users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function users(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filter by admin status
        if ($request->has('filter') && $request->filter !== 'all') {
            if ($request->filter === 'admin') {
                $query->where('is_admin', true);
            } elseif ($request->filter === 'regular') {
                $query->where('is_admin', false);
            } elseif ($request->filter === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->filter === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        // Get users without count first
        $users = $query->orderBy('created_at', 'desc')
            ->paginate(15);
        
        // Add loads count manually for each user
        foreach ($users as $user) {
            $user->loads_count = Load::where('user_id', $user->id)->count();
        }

        return view('admin.users', compact('users'));
    }

    /**
     * Show the form for editing a user.
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);
        $user->loads_count = Load::where('user_id', $id)->count();
        $recentOrders = Load::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.edit-user', compact('user', 'recentOrders'));
    }

    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'is_admin' => 'boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->is_admin = $request->has('is_admin');
        
        // Verify email if requested
        if ($request->has('verify_email') && !$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        return redirect()->route('admin.users.edit', $id)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete the specified user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Check if user has orders
        $orderCount = $user->loads()->count();
        if ($orderCount > 0) {
            return back()->with('error', "Cannot delete user with {$orderCount} orders. Consider deactivating instead.");
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "User '{$userName}' has been deleted.");
    }

    /**
     * Toggle user admin status.
     *
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleAdmin($id)
    {
        $user = User::findOrFail($id);

        // Prevent removing your own admin status
        if ($user->id === Auth::id() && $user->is_admin) {
            return back()->with('error', 'You cannot remove your own admin privileges.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        $message = $user->is_admin 
            ? "User '{$user->name}' is now an admin."
            : "Admin privileges removed from '{$user->name}'.";

        return back()->with('success', $message);
    }

    /**
     * Reset user password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = bcrypt($request->password);
        $user->save();

        return back()->with('success', "Password reset successfully for '{$user->name}'.");
    }
}