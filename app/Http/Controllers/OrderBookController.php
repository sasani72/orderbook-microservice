<?php

namespace App\Http\Controllers;

use App\Services\OrderBookService;
use Illuminate\Http\Request;

class OrderBookController extends Controller
{
    private $orderBookService;

    public function __construct(OrderBookService $orderBookService)
    {
        $this->orderBookService = $orderBookService;
    }

    public function getOrderBook(Request $request)
    {
        $symbol = $request->input('symbol');
        $depth = $request->input('depth', 100);

        $orderBook = $this->orderBookService->getOrderBook($symbol, $depth);

        return response()->json($orderBook);
    }
}
