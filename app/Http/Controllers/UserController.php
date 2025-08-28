<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\FilterUsersRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\User\DeleteUser;
use App\Services\User\UpdateUser;
use App\Services\User\CreateUser;
use App\Models\User;
use App\Services\User\ListUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class UserController extends BaseController
{
    public function index(FilterUsersRequest $request, ListUsers $service): AnonymousResourceCollection
    {
        $users = $service->list($request->validated());
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request, CreateUser $service): JsonResponse
    {
        $user = $service->create($request->validated());

        return $this->createdResponse(
            new UserResource($user),
            'Usuário criado com sucesso'
        );
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);
        $user->load('profileable');
        return new UserResource($user);
    }

    public function profile(Request $request): UserResource
    {
        $user = $request->user();
        $user->load('profileable');
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $service): JsonResponse
    {
        $user = $service->update($user, $request->validated());

        return $this->updatedResponse(
            new UserResource($user),
            'Usuário atualizado com sucesso'
        );
    }

    public function destroy(User $user, DeleteUser $service): JsonResponse
    {
        $this->authorize('delete', $user);
        $service->delete($user);

        return $this->deletedResponse('Usuário excluído com sucesso');
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        return $this->successResponse(
            new UserResource($user),
            'Usuário ativado com sucesso'
        );
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        // Revoke all tokens when deactivating
        $user->tokens()->delete();
        $user->update(['is_active' => false]);

        return $this->successResponse(
            new UserResource($user),
            'Usuário desativado com sucesso'
        );
    }
}
