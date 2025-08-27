<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StaffUserProfile\FilterStaffUsersRequest;
use App\Http\Requests\StaffUserProfile\StoreStaffUserRequest;
use App\Http\Requests\StaffUserProfile\UpdateStaffUserRequest;
use App\Http\Resources\StaffUserProfileResource;
use App\Services\StaffUserProfile\DeleteStaffUser;
use App\Services\StaffUserProfile\UpdateStaffUser;
use App\Services\StaffUserProfile\CreateStaffUser;
use App\Models\StaffUserProfile;
use App\Services\StaffUserProfile\ListStaffUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StaffUserProfileController extends Controller
{
    use AuthorizesRequests;

    public function index(FilterStaffUsersRequest $request, ListStaffUsers $service): AnonymousResourceCollection
    {
        $staffUsers = $service->list($request->validated());
        return StaffUserProfileResource::collection($staffUsers);
    }

    public function store(StoreStaffUserRequest $request, CreateStaffUser $service): JsonResponse
    {
        $staffUser = $service->create($request->validated());

        return (new StaffUserProfileResource($staffUser))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StaffUserProfile $staffUserProfile): StaffUserProfileResource
    {
        $this->authorize('view', $staffUserProfile);
        $staffUserProfile->load('user');
        return new StaffUserProfileResource($staffUserProfile);
    }

    public function update(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile, UpdateStaffUser $service): StaffUserProfileResource
    {
        $staffUserProfile = $service->update($staffUserProfile, $request->validated());
        return new StaffUserProfileResource($staffUserProfile);
    }

    public function destroy(StaffUserProfile $staffUserProfile, DeleteStaffUser $service): JsonResponse
    {
        $this->authorize('delete', $staffUserProfile);
        $service->delete($staffUserProfile);
        return response()->json(null, 204);
    }

    public function updatePermissions(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile): JsonResponse
    {
        $this->authorize('update', $staffUserProfile);

        $staffUserProfile->update([
            'system_permissions' => $request->validated()['system_permissions'] ?? [],
        ]);

        return response()->json([
            'message' => 'Permissões atualizadas com sucesso',
            'staff_user' => new StaffUserProfileResource($staffUserProfile)
        ]);
    }

    public function updateAccessLevel(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile): JsonResponse
    {
        $this->authorize('update', $staffUserProfile);

        $staffUserProfile->update([
            'access_level' => $request->validated()['access_level'],
        ]);

        return response()->json([
            'message' => 'Nível de acesso atualizado com sucesso',
            'staff_user' => new StaffUserProfileResource($staffUserProfile)
        ]);
    }
}
