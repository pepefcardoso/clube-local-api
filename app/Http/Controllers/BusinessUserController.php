<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessUser\StoreBusinessUserRequest;
use App\Http\Requests\BusinessUser\UpdateBusinessUserRequest;
use App\Http\Resources\BusinessUserResource;
use App\Http\Resources\Collections\BusinessUserCollection;
use App\Models\BusinessUser;
use App\Services\BusinessUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessUserController extends BaseApiController
{
    public function __construct(
        private BusinessUserService $businessUserService
    ) {
        $this->authorizeResource(BusinessUser::class, 'business_user');
    }

    public function index(Request $request): JsonResponse
    {
        $businessUsers = $this->businessUserService->getBusinessUsers($request);

        return $this->collectionResponse(
            new BusinessUserCollection($businessUsers)
        );
    }

    public function store(StoreBusinessUserRequest $request): JsonResponse
    {
        $businessUser = $this->businessUserService->createBusinessUser($request->validated());

        return $this->resourceResponse(
            new BusinessUserResource($businessUser),
            'Business user created successfully'
        )->setStatusCode(201);
    }

    public function show(BusinessUser $businessUser): JsonResponse
    {
        $businessUser = $this->businessUserService->getBusinessUser($businessUser);

        return $this->resourceResponse(
            new BusinessUserResource($businessUser)
        );
    }

    public function update(UpdateBusinessUserRequest $request, BusinessUser $businessUser): JsonResponse
    {
        $businessUser = $this->businessUserService->updateBusinessUser($businessUser, $request->validated());

        return $this->resourceResponse(
            new BusinessUserResource($businessUser),
            'Business user updated successfully'
        );
    }

    public function destroy(BusinessUser $businessUser): JsonResponse
    {
        $this->businessUserService->deleteBusinessUser($businessUser);

        return $this->successResponse(
            message: 'Business user deleted successfully'
        );
    }

    public function register(StoreBusinessUserRequest $request): JsonResponse
    {
        $result = $this->businessUserService->registerBusinessUser($request->validated());

        return response()->json([
            'message' => 'Business user registered successfully',
            'user' => [
                'id' => $result['business_user']->id,
                'name' => $result['business_user']->name,
                'email' => $result['business_user']->email,
                'type' => 'business_user',
                'roles' => $result['business_user']->getRoleNames(),
            ],
            'token' => $result['token'],
            'expires_at' => $result['expires_at'],
        ], 201);
    }
}
