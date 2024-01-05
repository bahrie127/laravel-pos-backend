<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    //index
    public function index()
    {
        $orders = \App\Models\Order::with('kasir')->orderBy('created_at', 'desc')->paginate(10);

        return view('pages.orders.index', compact('orders'));
    }

    //view
    public function show($id)
    {
        $order = \App\Models\Order::with('kasir')->findOrFail($id);

        //get order items by order id
        $orderItems = \App\Models\OrderItem::with('product')->where('order_id', $id)->get();


        return view('pages.orders.view', compact('order', 'orderItems'));
    }
}
