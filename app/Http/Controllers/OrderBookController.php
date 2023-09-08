<?php

namespace App\Http\Controllers;

use App\Services\OrderBookService;
use App\Services\RequestValidator;
use Illuminate\Http\Request;
use InvalidRequestException;

class OrderBookController extends Controller
{
    private $orderBookService;
    private $requestValidator;

    public function __construct(OrderBookService $orderBookService, RequestValidator $requestValidator)
    {
        $this->orderBookService = $orderBookService;
        $this->requestValidator = $requestValidator;
    }

    public function getOrderBook(Request $request)
    {
        try {
            $symbol = $request->input('symbol');
            $depth = $request->input('depth', 100);

            $this->requestValidator->validate($symbol, $depth);

            $orderBook = $this->orderBookService->getOrderBook($symbol, $depth);

            return response()->json($orderBook);
        } catch (InvalidRequestException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }
}
