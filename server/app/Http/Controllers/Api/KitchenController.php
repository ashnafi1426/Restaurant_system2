<?php

namespace App\Http\Controllers\Api;

use App\Services\KitchenService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use Throwable;


class KitchenController extends Controller
{

    protected KitchenService $kitchenService;
    public function __construct(
        KitchenService $kitchenService
    )
    {
        $this->kitchenService = $kitchenService;
    }
    public function index(): JsonResponse
    {

        try {

            $orders = $this
                ->kitchenService
                ->getKitchenOrders(auth()->user());



            return response()->json([

                'success' => true,

                'message' => 'Kitchen orders retrieved successfully.',

                'data' => [

                    'pending' =>
                        KitchenOrderResource::collection(
                            $orders['pending']
                        ),


                    'preparing' =>
                        KitchenOrderResource::collection(
                            $orders['preparing']
                        ),


                    'ready' =>
                        KitchenOrderResource::collection(
                            $orders['ready']
                        ),


                    'served' =>
                        KitchenOrderResource::collection(
                            $orders['served']
                        ),

                ]

            ]);


        } catch(Throwable $e){


            return response()->json([

                'success'=>false,

                'message'=>'Failed to load kitchen orders.',

                'error'=>$e->getMessage()

            ],500);


        }

    }
    public function start(Order $order): JsonResponse
    {

        try {

            \Log::info('🟢 [KITCHEN] START Action Received', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
            ]);


            $updatedOrder =
                $this
                ->kitchenService
                ->startPreparing($order);

            \Log::info('✅ [KITCHEN] START Action Completed', [
                'order_id' => $updatedOrder->id,
                'new_status' => $updatedOrder->status,
            ]);

            return response()->json([

                'success'=>true,

                'message'=>
                    'Order started preparing successfully.',


                'data'=>
                    new KitchenOrderResource(
                        $updatedOrder
                    )

            ]);



        }catch(Throwable $e){

            \Log::error('❌ [KITCHEN] START Action Failed', [
                'order_id' => $order->id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }


    }
    public function ready(Order $order): JsonResponse
    {

        try {

            \Log::info('🟢 [KITCHEN] READY Action Received', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
            ]);

            $updatedOrder =
                $this
                ->kitchenService
                ->markReady($order);

            \Log::info('✅ [KITCHEN] READY Action Completed', [
                'order_id' => $updatedOrder->id,
                'new_status' => $updatedOrder->status,
            ]);

            return response()->json([

                'success'=>true,

                'message'=>
                    'Order marked as ready.',


                'data'=>
                    new KitchenOrderResource(
                        $updatedOrder
                    )


            ]);



        }catch(Throwable $e){

            \Log::error('❌ [KITCHEN] READY Action Failed', [
                'order_id' => $order->id ?? 'unknown',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }


    }
    public function complete(Order $order): JsonResponse
    {

        try {

            \Log::info('🟢 [KITCHEN] COMPLETE Action Received', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
            ]);

            $updatedOrder =
                $this
                ->kitchenService
                ->markServed($order);

            \Log::info('✅ [KITCHEN] COMPLETE Action Completed', [
                'order_id' => $updatedOrder->id,
                'new_status' => $updatedOrder->status,
            ]);

            return response()->json([

                'success'=>true,

                'message'=>
                    'Order completed successfully.',


                'data'=>
                    new KitchenOrderResource(
                        $updatedOrder
                    )


            ]);



        }catch(Throwable $e){

            \Log::error('❌ [KITCHEN] COMPLETE Action Failed', [
                'order_id' => $order->id ?? 'unknown',
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }


    }







    /**
     * Kitchen statistics
     *
     * GET /api/kitchen/statistics
     */
    public function statistics(): JsonResponse
    {

        try {


            $statistics =
                $this
                ->kitchenService
                ->statistics(auth()->user());



            return response()->json([

                'success'=>true,

                'data'=>$statistics

            ]);



        }catch(Throwable $e){


            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }

    }


}