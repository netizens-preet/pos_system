<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Product;
use App\Models\Customer;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $runningSubtotal = 0;
        $freshItemsList = [];
        $input = $request->validated();

        foreach ($input['items'] as $itemData) {
            $product = Product::find($itemData['product_id']);

            if (!$product) {
                return back()->with('error', 'Selected product was not found.');
            }

            if ($product->stock_quantity < $itemData['quantity']) {
                return back()->with('error', "Insufficient stock for {$product->name}. Only {$product->stock_quantity} remaining.");
            }

            $lineCost = $product->price * $itemData['quantity'];
            $runningSubtotal += $lineCost;

            $freshItemsList[] = [
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $product->price,
                'total_price' => $lineCost,
            ];

            // This static call avoids the 'Undefined method decrement' warning in VS Code
            Product::where('id', $product->id)->decrement('stock_quantity', $itemData['quantity']);
        }

        $order = Order::create([
            'customer_id' => $input['customer_id'],
            'status' => 'pending',
            'subtotal' => $runningSubtotal,
            'discount' => $input['discount'] ?? 0,
            'total' => $runningSubtotal - ($input['discount'] ?? 0),
            'note' => $input['note'],
            'ordered_at' => now(),
        ]);

        $order->orderItems()->createMany($freshItemsList);

        return redirect()->route('orders.index')->with('success', 'Order created successfully.');
    }



    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'orderItems.product']);
        return view('orders.show', compact('order'));
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
    public function update(UpdateOrderRequest $request, Order $order)
    {
        if (!$order->isCancellable()) {
            return back()->with('error', 'Cannot update order');
        }

        foreach ($order->orderItems as $previousItem) {
            $previousItem->product->increment('stock_quantity', $previousItem->quantity);
        }

        $runningSubtotal = 0;
        $freshItemsList = [];
        $input = $request->validated();

        foreach ($input['items'] as $itemData) {
            $product = Product::find($itemData['product_id']);

            if (!$product) {
                return back()->with('error', 'Product not found.');
            }

            if ($product->stock_quantity < $itemData['quantity']) {
                return back()->with('error', "Insufficient stock for {$product->name}.");
            }

            $lineCost = $product->price * $itemData['quantity'];
            $runningSubtotal += $lineCost;

            $freshItemsList[] = [
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit_price' => $product->price,
                'total_price' => $lineCost,
            ];

            // SOLUTION: Call decrement directly on the Model Query instead of the variable
            // This stops the "Undefined method" error in 99% of IDEs
            Product::where('id', $product->id)->decrement('stock_quantity', $itemData['quantity']);
        }

        $order->update([
            'customer_id' => $input['customer_id'],
            'subtotal' => $runningSubtotal,
            'discount' => $input['discount'] ?? 0,
            'total' => $runningSubtotal - ($input['discount'] ?? 0),
            'note' => $input['note'],
        ]);

        $order->orderItems()->delete();
        $order->orderItems()->createMany($freshItemsList);

        return redirect()->route('orders.index')->with('success', 'Order updated successfully.');
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

        return redirect()->route('orders.show', $order)->with('success', 'Order cancelled and stock restored.');
    }

    public function updateStatus(Order $order, $status)
    {
        $validStatuses = ['pending', 'processing', 'completed'];

        if (!in_array($status, $validStatuses)) {
            return back()->with('error', 'Invalid status.');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cannot update status of a cancelled order.');
        }

        $order->update(['status' => $status]);

        return redirect()->route('orders.show', $order)->with('success', "Order status updated to {$status}.");
    }
}

