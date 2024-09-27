<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Constants\HttpStatusCodes;
use Illuminate\Support\Facades\Validator;
use App\Services\RestaurantService;

class RestaurantController extends Controller
{
    protected $service;

    public function __construct(RestaurantService $restaurantService)
    {
        $this->service = $restaurantService;
    }

    protected function generateMessage($type = null){
        $translated = trans('messages', [], 'id');
        $message = '';
        switch ($type) {
            case 'create':
                $message = $translated['created successfully'];
                break;

            case 'update':
                $message = $translated['updated successfully'];
                break;

            case 'delete':
                $message = $translated['deleted successfully'];
                break;

            case 'restore':
                $message = $translated['restored successfully'];
                break;

            default:
                $message = $translated['success'];
                break;
        }

        return ucwords($message);
    }

    public function index(Request $request)
    {
        $meta['orderBy'] = $request->orderBy ?: 'asc';
        $meta['limit'] = $request->limit <= 30 ? $request->limit : 30;
        
        $dataTable = $this->service->getDatatable($request, $meta);

        return response()->json([
            'error' => false,
            'message' => 'Successfully',
            'status_code' => HttpStatusCodes::HTTP_OK,
            'data' => $dataTable['data'],
            'pagination' => $dataTable['meta']
        ], HttpStatusCodes::HTTP_OK);
    }

    public function runValidationShow($id)
    {
        return Validator::make(['id' => $id], [
            'id' => 'required|exists:restaurants,id,deleted_at,NULL'
        ]);
    }

    public function show(Request $request, $id)
    {
        $validator = $this->runValidationShow($id);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->all()[0], 
                HttpStatusCodes::HTTP_BAD_REQUEST
           
            );
        }

        try {
            $data = $this->service->getDetailByID($id);

            return $this->successResponse('Successfully', $data);

        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    public function runValidationCreate($request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|unique:restaurants,name,NULL,id,deleted_at,NULL',
            'address' => 'required',
        ]);
    }

    public function store(Request $request)
    {
        $validator = $this->runValidationCreate($request);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->all()[0], 
                HttpStatusCodes::HTTP_BAD_REQUEST
            );
        }

        try {
            $data =  $this->service->store($request);

            return $this->successResponse('Successfully', $data);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }
    }

    public function runValidationUpdate($request)
    {
        return Validator::make($request->all(), [
            'id' => 'required|exists:restaurants,id,deleted_at,NULL',
            'name' => 'required|unique:restaurants,name,'.$request->id.',id,deleted_at,NULL',
            'address' => 'required',
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->merge(['id' => $id]);

        $validator = $this->runValidationUpdate($request);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->all()[0], 
                HttpStatusCodes::HTTP_BAD_REQUEST
            );
        }

        try {
            $data = $this->service->update($id, $request);

            return $this->successResponse('Successfully', $data);
        } catch (\Throwable $th) {

            return $this->errorResponse($th->getMessage());
        }
    }

    public function destroy(Request $request)
    {
        $validator = $this->runValidationShow($id);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->all()[0], 
                HttpStatusCodes::HTTP_BAD_REQUEST
           
            );
        }

        try {
            $data = $this->service->delete($request->id);

            return $this->successResponse('Successfully');
        } catch (\Throwable $th) {
            
            return $this->errorResponse($th->getMessage());
        }
    }

    private function errorResponse($message, $statusCode = HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR)
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'status_code' => $statusCode,
        ], $statusCode);
    }

    private function successResponse($message, $data = '', $statusCode = HttpStatusCodes::HTTP_OK)
    {
        $response = [
            'error' => false,
            'message' => $message,
            'status_code' => $statusCode
        ];

        if ($data !== '') {
            $response['data'] = $data;
        }


        return response()->json($response, $statusCode);
    }
}
