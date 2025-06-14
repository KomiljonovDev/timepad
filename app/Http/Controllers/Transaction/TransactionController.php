<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\ListTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Services\Contracts\TransactionServiceInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    protected TransactionServiceInterface $service;

    public function __construct(TransactionServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index(ListTransactionRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $this->service->setFilters($filters);
            $data = $this->service->get();
            return response()->json(['data' => $data], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve transactions'], 500);
        }
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            $items = array_merge($request->validated(), ['server_received_timestamp'=>now()->timestamp]);
            $data = $this->service->create($items);
            return response()->json(['data' => $data, 'message' => 'Transaction created successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create transaction'], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $service = $this->service;
            $data = $service->setRelation('user');
            $data = $data->show($id);
            if (!$data) {
                throw new ModelNotFoundException();
            }
            return response()->json(['data' => $data], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Transaction not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve transaction'], 500);
        }
    }

    public function update(UpdateTransactionRequest $request, $id): JsonResponse
    {
        try {
            $data = $this->service->edit($request->validated(), $id);
            if (!$data) {
                throw new ModelNotFoundException();
            }
            return response()->json(['data' => $data, 'message' => 'Transaction updated successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Transaction not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update transaction'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $result = $this->service->delete($id);
            if (!$result) {
                throw new ModelNotFoundException();
            }
            return response()->json(['message' => 'Transaction deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Transaction not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete transaction'], 500);
        }
    }
}
