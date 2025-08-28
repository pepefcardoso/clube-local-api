<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Auth;

class StaffUserProfileController extends BaseController
{
    public function index(FilterStaffUsersRequest $request, ListStaffUsers $service): AnonymousResourceCollection
    {
        $staffUsers = $service->list($request->validated());
        return StaffUserProfileResource::collection($staffUsers);
    }

    public function store(StoreStaffUserRequest $request, CreateStaffUser $service): JsonResponse
    {
        $this->authorize('create', StaffUserProfile::class);

        $data = $request->validated();

        if ($data['access_level'] === 'admin') {
            $this->authorize('createAdmin', StaffUserProfile::class);
        }

        $staffUser = $service->create($data);

        return $this->createdResponse(
            new StaffUserProfileResource($staffUser),
            'Usuário staff criado com sucesso'
        );
    }

    public function show(StaffUserProfile $staffUserProfile): StaffUserProfileResource
    {
        $this->authorize('view', $staffUserProfile);
        $staffUserProfile->load('user');
        return new StaffUserProfileResource($staffUserProfile);
    }

    public function update(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile, UpdateStaffUser $service): JsonResponse
    {
        $this->authorize('update', $staffUserProfile);

        $data = $request->validated();

        if (isset($data['access_level'])) {
            $newLevel = $data['access_level'];
            $currentLevel = $staffUserProfile->access_level;

            if ($newLevel === 'admin' && $currentLevel !== 'admin') {
                $this->authorize('promoteToAdmin', $staffUserProfile);
            }

            if ($currentLevel === 'admin' && $newLevel !== 'admin') {
                $this->authorize('demoteFromAdmin', $staffUserProfile);
            }
        }

        $staffUserProfile = $service->update($staffUserProfile, $data);

        return $this->updatedResponse(
            new StaffUserProfileResource($staffUserProfile),
            'Usuário staff atualizado com sucesso'
        );
    }

    public function destroy(StaffUserProfile $staffUserProfile, DeleteStaffUser $service): JsonResponse
    {
        $this->authorize('delete', $staffUserProfile);
        $service->delete($staffUserProfile);

        return $this->deletedResponse('Usuário staff excluído com sucesso');
    }

    public function updatePermissions(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile): JsonResponse
    {
        $this->authorize('update', $staffUserProfile);

        $validated = $request->validated();

        if (!isset($validated['system_permissions'])) {
            return $this->errorResponse('Campo system_permissions é obrigatório', 422);
        }

        $staffUserProfile->update([
            'system_permissions' => $validated['system_permissions'],
        ]);

        return $this->successResponse(
            new StaffUserProfileResource($staffUserProfile),
            'Permissões atualizadas com sucesso'
        );
    }

    public function updateAccessLevel(UpdateStaffUserRequest $request, StaffUserProfile $staffUserProfile): JsonResponse
    {
        $this->authorize('update', $staffUserProfile);

        $validated = $request->validated();

        if (!isset($validated['access_level'])) {
            return $this->errorResponse('Campo access_level é obrigatório', 422);
        }

        $staffUserProfile->update([
            'access_level' => $validated['access_level'],
        ]);

        if ($staffUserProfile->user && Auth::id() === $staffUserProfile->user->id) {
            $staffUserProfile->user->generateApiToken();
        }

        return $this->successResponse(
            new StaffUserProfileResource($staffUserProfile),
            'Nível de acesso atualizado com sucesso'
        );
    }
}
