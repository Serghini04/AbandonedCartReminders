<?php
namespace App\Http\Controllers;

use App\Services\Cart\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}
    
    public function addProduct(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'integer|min:1'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
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
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $cart = $this->cartService->getActiveCart($request->customer_email);
        
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'No active cart found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $cart
        ]);
    }
    
    public function finalizeCart(Request $request, int $cartId): JsonResponse
    {
        try {
            $cart = $this->cartService->finalizeCart($cartId);
            
            return response()->json([
                'success' => true,
                'message' => 'Cart finalized successfully',
                'data' => $cart
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to finalize cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function completeFromEmail(Request $request, int $cartId): JsonResponse
    {
        $expectedToken = hash_hmac('sha256', $cartId, config('app.key'));
        
        if ($request->token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 403);
        }
        
        return $this->finalizeCart($request, $cartId);
    }
}