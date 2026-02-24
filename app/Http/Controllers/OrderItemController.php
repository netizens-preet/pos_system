<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\order_item;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderItemsRequest;
use App\Http\Requests\UpdateOrderItemsRequest;
use Illuminate\Support\Facades\DB;
class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Order $order)
    {
        $items = order_item::where('order_id', $order->id)
                      ->with('product')
                      ->get();
                      
    return view('order_items.index', compact('items', 'order'));
    }

    /**
     * Show the form for creating a new resource.
     */
public function create(Order $order)
{
    // Fetch products that have stock > 0
    $products = Product::where('stock_quantity', '>', 0)->get();
    
    return view('order_items.create', compact('order', 'products'));
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $product = Product::find($request->product_id);

    if (!$product) {
        return back()->with('error', 'Product not found.');
    }

    if ($product->stock_quantity < $request->quantity) {
        return back()->with('error', 'Insufficient stock.');
    }

    $unitCost = $product->price;
    $totalCost = $unitCost * $request->quantity;

    order_item::create([
        'order_id'   => $request->order_id,
        'product_id' => $request->product_id,
        'quantity'   => $request->quantity,
        'unit_price' => $unitCost,
        'total_price' => $totalCost,
    ]);

    Product::where('id', $product->id)->decrement('stock_quantity', $request->quantity);

    return redirect()->back()->with('success', 'Item added to order.');
}
    /**
     * Display the specified resource.
     */
    public function show(order_item $order_item)
    {
        $order_item->load('product');
    
    return view('order_items.show', compact('order_item'));
}
    

    /**
     * Show the form for editing the specified resource.
     */
   // app/Http/Controllers/OrderItemController.php

public function edit(order_item $orderItem)
{
    // Eager load the product to show current price/name in the form
    $orderItem->load('product');
    
    // Fetch all products in case the user wants to switch the product
    $products = Product::all();
    
    return view('order_items.edit', compact('orderItem', 'products'));
}
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderItemsRequest $request, order_item $orderItem)
{
    $product = $orderItem->product;
    $input = $request->validated();

    if (!$product) {
        return back()->with('error', 'Associated product not found.');
    }

    Product::where('id', $product->id)->increment('stock_quantity', $orderItem->quantity);

    $product->refresh();

    if ($product->stock_quantity < $input['quantity']) {
        Product::where('id', $product->id)->decrement('stock_quantity', $orderItem->quantity);
        return back()->with('error', "Insufficient stock for {$product->name}.");
    }

    $orderItem->update([
        'quantity'    => $input['quantity'],
        'total_price' => $orderItem->unit_price * $input['quantity']
    ]);

    Product::where('id', $product->id)->decrement('stock_quantity', $input['quantity']);

    return redirect()->back()->with('success', 'Item updated successfully.');
}

    /**
     * Remove the specified resource from storage.
     */
public function destroy(order_item $orderItem)
{
    Product::where('id', $orderItem->product_id)->increment('stock_quantity', $orderItem->quantity);
    
    $orderItem->delete();

    return redirect()->back()->with('success', 'Item removed.');
}
}
