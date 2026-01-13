<?php
namespace App\Http\Controllers;

use App\Http\Requests\AddProductToCartRequest;
use App\Http\Requests\GetCartRequest;
use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}
    
    public function addProduct(AddProductToCartRequest $request): JsonResponse
    {
        try {
            $cartItem = $this->cartService->addProduct(
                $request->customer_email,
                $request->product_id,
                $request->quantity ?? 1
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'data' => [
                    'cart_id' => $cartItem->cart_id,
                    'cart_item' => $cartItem->load('product')
                ]
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            Log::error('Failed to add product to cart', [
                'customer_email' => $request->customer_email,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity ?? 1,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function getCart(GetCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->getActiveCart($request->customer_email);
        
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'No active cart found'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json([
            'success' => true,
            'data' => $cart
        ]);
    }
    
    public function finalizeCart(int $cartId): JsonResponse
    {
        try {
            $cart = $this->cartService->finalizeCart($cartId);
            
            return response()->json([
                'success' => true,
                'message' => 'Cart finalized successfully',
                'data' => $cart
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to finalize cart', [
                'cart_id' => $cartId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize cart. Please try again later.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function completeFromEmail(Request $request, int $cartId): JsonResponse
    {
        $expectedToken = hash_hmac('sha256', $cartId, config('app.key'));
        
        if ($request->token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], Response::HTTP_FORBIDDEN);
        }
        
        return $this->finalizeCart($cartId);
    }
}