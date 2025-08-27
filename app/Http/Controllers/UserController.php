<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(FilterUsersRequest $request, ListUsers $service): AnonymousResourceCollection
    {
        $users = $service->list($request->validated());
        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request, CreateUser $service): JsonResponse
    {
        $user = $service->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
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

    public function update(UpdateUserRequest $request, User $user, UpdateUser $service): UserResource
    {
        $user = $service->update($user, $request->validated());
        return new UserResource($user);
    }

    public function destroy(User $user, DeleteUser $service): JsonResponse
    {
        $this->authorize('delete', $user);
        $service->delete($user);
        return response()->json(null, 204);
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'Usuário ativado com sucesso',
            'user' => new UserResource($user)
        ]);
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user->tokens()->delete();
        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'Usuário desativado com sucesso',
            'user' => new UserResource($user)
        ]);
    }
}
