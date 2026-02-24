<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;

use App\Models\{Product, OrderItem, Customer};
use Illuminate\Http\Requests\UpdateOrderRequest;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('customer')->latest()->get();
        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::all();
        $products = Product::where('stock_quantity', '>', 0)->get();
        return view('orders.create', compact('customers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        
        // Data is already validated (customer exists, items are valid)
        return DB::transaction(function () use ($request) {
            $subtotal = 0;
            $orderItems = [];
            $validatedData = $request->validated();

            foreach ($validatedData['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                // Business logic check: Stock availability
                if ($product->stock_quantity < $item['quantity']) {
                    // We throw an exception to trigger the DB rollback
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. available: {$product->stock_quantity}"
                    ]);
                }

                $lineTotal = $product->price * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id'  => $product->id,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $product->price,
                    'total_price' => $lineTotal,
                ];

                $product->decrement('stock_quantity', $item['quantity']);
            }

            $order = Order::create([
                'customer_id' => $validatedData['customer_id'],
                'status'      => 'pending',
                'subtotal'    => $subtotal,
                'discount'    => $validatedData['discount'] ?? 0,
                'total'       => $subtotal - ($validatedData['discount'] ?? 0),
                'note'        => $validatedData['note'],
                'ordered_at'  => now(),
            ]);

            $order->orderItems()->createMany($orderItems);

            return redirect()->route('orders.index')->with('success', 'Order created successfully.');
        });
    }

    

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $order->load('orderItems.product');
    $customers = Customer::all();
    $products = Product::where('stock_quantity', '>', 0)->get();

    return view('orders.edit', compact('order', 'customers', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(StoreOrderRequest $request, Order $order)
{
    // Only allow updates if the order isn't already processed/cancelled
    if (!$order->isCancellable()) {
        return back()->with('error', 'Cannot update an order that is not pending.');
    }

    return DB::transaction(function () use ($request, $order) {
        // 1. Restore Stock from current items before changing them
        foreach ($order->orderItems as $oldItem) {
            $oldItem->product->increment('stock_quantity', $oldItem->quantity);
        }

        $subtotal = 0;
        $newOrderItems = [];
        $validatedData = $request->validated();

        // 2. Process New Items
        foreach ($validatedData['items'] as $item) {
            $product = Product::lockForUpdate()->find($item['product_id']);

            // Check if new quantities are available
            if ($product->stock_quantity < $item['quantity']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name} during update."
                ]);
            }

            $lineTotal = $product->price * $item['quantity'];
            $subtotal += $lineTotal;

            $newOrderItems[] = [
                'product_id'  => $product->id,
                'quantity'    => $item['quantity'],
                'unit_price'  => $product->price,
                'total_price' => $lineTotal,
            ];

            // Deduct new stock
            $product->decrement('stock_quantity', $item['quantity']);
        }

        // 3. Update the Order record
        $order->update([
            'customer_id' => $validatedData['customer_id'],
            'subtotal'    => $subtotal,
            'discount'    => $validatedData['discount'] ?? 0,
            'total'       => $subtotal - ($validatedData['discount'] ?? 0),
            'note'        => $validatedData['note'],
        ]);

        // 4. Replace old items with new items
        $order->orderItems()->delete();
        $order->orderItems()->createMany($newOrderItems);

        return redirect()->route('orders.index')->with('success', 'Order updated successfully.');
    });
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
    public function cancel(Order $order)
    {
        if (!$order->isCancellable()) {
            return back()->with('error', 'Only pending orders can be cancelled.');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
            $order->update(['status' => 'cancelled']);
        });

        return redirect()->route('orders.index')->with('success', 'Order cancelled and stock restored.');
    }

}
