<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

abstract class BaseUserService
{
    abstract protected function getModel(): string;
    abstract protected function getDefaultData(): array;

    public function getUsers(Request $request): LengthAwarePaginator
    {
        $model = $this->getModel();

        return $model::query()
            ->with('roles:id,name')
            ->filter($request)
            ->when(!$request->has('sort'), fn($q) => $q->latest())
            ->paginate($request->get('per_page', 15));
    }

    public function getUser(Model $user): Model
    {
        return $user->load('roles', 'permissions');
    }

    public function createUser(array $data): Model
    {
        $data['password'] = Hash::make($data['password']);
        $data = array_merge($this->getDefaultData(), $data);

        $model = $this->getModel();
        $user = $model::create($data);

        return $user->load('roles');
    }

    public function updateUser(Model $user, array $data): Model
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user->load('roles');
    }

    public function deleteUser(Model $user): bool
    {
        $user->tokens()->delete();
        return $user->delete();
    }

    public function registerUser(array $data): array
    {
        $user = $this->createUser($data);

        $token = $user->createToken(
            name: class_basename($user) . '_token',
            abilities: $user->getTokenAbilities(),
            expiresAt: now()->addDays(30)
        );

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }
}
