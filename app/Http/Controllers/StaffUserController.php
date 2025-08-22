<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffUser\StoreStaffUserRequest;
use App\Http\Requests\StaffUser\UpdateStaffUserRequest;
use App\Http\Resources\StaffUserResource;
use App\Http\Resources\Collections\StaffUserCollection;
use App\Models\StaffUser;
use App\Services\StaffUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffUserController extends BaseApiController
{
    public function __construct(
        private StaffUserService $staffUserService
    ) {
        $this->authorizeResource(StaffUser::class, 'staff_user');
    }

    public function index(Request $request): JsonResponse
    {
        $staffUsers = $this->staffUserService->getStaffUsers($request);

        return $this->collectionResponse(
            new StaffUserCollection($staffUsers)
        );
    }

    public function store(StoreStaffUserRequest $request): JsonResponse
    {
        $staffUser = $this->staffUserService->createStaffUser($request->validated());

        return $this->resourceResponse(
            new StaffUserResource($staffUser),
            'Staff user created successfully'
        )->setStatusCode(201);
    }

    public function show(StaffUser $staffUser): JsonResponse
    {
        $staffUser = $this->staffUserService->getStaffUser($staffUser);

        return $this->resourceResponse(
            new StaffUserResource($staffUser)
        );
    }

    public function update(UpdateStaffUserRequest $request, StaffUser $staffUser): JsonResponse
    {
        $staffUser = $this->staffUserService->updateStaffUser($staffUser, $request->validated());

        return $this->resourceResponse(
            new StaffUserResource($staffUser),
            'Staff user updated successfully'
        );
    }

    public function destroy(StaffUser $staffUser): JsonResponse
    {
        $this->staffUserService->deleteStaffUser($staffUser);

        return $this->successResponse(
            message: 'Staff user deleted successfully'
        );
    }

    public function register(StoreStaffUserRequest $request): JsonResponse
    {
        $result = $this->staffUserService->createStaffUser($request->validated());

        return response()->json([
            'message' => 'Staff user registered successfully',
            'user' => [
                'id' => $result['staff_user']->id,
                'name' => $result['staff_user']->name,
                'email' => $result['staff_user']->email,
                'type' => 'staff_user',
                'roles' => $result['staff_user']->getRoleNames(),
            ],
            'token' => $result['token'],
            'expires_at' => $result['expires_at'],
        ], 201);
    }
}
