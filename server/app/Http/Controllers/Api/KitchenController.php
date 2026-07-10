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
                ->getKitchenOrders();



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


            $updatedOrder =
                $this
                ->kitchenService
                ->startPreparing($order);



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


            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }


    }
    public function ready(Order $order): JsonResponse
    {

        try {


            $updatedOrder =
                $this
                ->kitchenService
                ->markReady($order);



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


            return response()->json([

                'success'=>false,

                'message'=>$e->getMessage()

            ],500);


        }


    }
    public function complete(Order $order): JsonResponse
    {

        try {


            $updatedOrder =
                $this
                ->kitchenService
                ->markServed($order);



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
                ->statistics();



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