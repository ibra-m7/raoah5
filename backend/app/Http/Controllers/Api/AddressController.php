<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAddressRequest;
use App\Http\Requests\Api\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Services\Addresses\AddressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            'عناوين التوصيل',
            $this->addresses->collectionPayload($request->user()),
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addresses->create($request->user(), $request->validated());

        return ApiResponse::success('تم حفظ العنوان.', [
            'address' => (new AddressResource($address))->resolve(),
            'addresses' => $this->addresses->collectionPayload($request->user()),
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        try {
            $updated = $this->addresses->update($request->user(), $address, $request->validated());
        } catch (NotFoundHttpException) {
            return ApiResponse::error('العنوان غير موجود.', 404);
        }

        return ApiResponse::success('تم تحديث العنوان.', [
            'address' => (new AddressResource($updated))->resolve(),
            'addresses' => $this->addresses->collectionPayload($request->user()),
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        try {
            $this->addresses->delete($request->user(), $address);
        } catch (NotFoundHttpException) {
            return ApiResponse::error('العنوان غير موجود.', 404);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'تعذر حذف العنوان.';

            return ApiResponse::error($message, 422, $e->errors());
        }

        return ApiResponse::success('تم حذف العنوان.', [
            'addresses' => $this->addresses->collectionPayload($request->user()),
        ]);
    }
}
