<?php

namespace App\Http\Controllers;

use App\Http\Requests\Business\FilterBusinessesRequest;
use App\Http\Requests\Business\StoreBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Http\Resources\BusinessResource;
use App\Services\Business\CreateBusiness;
use App\Services\Business\UpdateBusiness;
use App\Services\Business\DeleteBusiness;
use App\Services\Business\ListBusinesses;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends BaseController
{
    public function index(FilterBusinessesRequest $request, ListBusinesses $service): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Business::class);

        $businesses = $service->list($request->validated());
        return BusinessResource::collection($businesses);
    }

    public function store(StoreBusinessRequest $request, CreateBusiness $service): JsonResponse
    {
        $this->authorize('create', Business::class);

        $business = $service->create($request->validated());

        return $this->createdResponse(
            new BusinessResource($business),
            'Empresa criada com sucesso'
        );
    }

    public function show(Business $business): BusinessResource
    {
        $this->authorize('view', $business);

        $business->load(['businessUserProfiles.user', 'customers', 'platformPlan', 'addresses']);
        return new BusinessResource($business);
    }

    public function update(UpdateBusinessRequest $request, Business $business, UpdateBusiness $service): JsonResponse
    {
        $this->authorize('update', $business);

        $updatedBusiness = $service->update($business, $request->validated());

        return $this->updatedResponse(
            new BusinessResource($updatedBusiness),
            'Empresa atualizada com sucesso'
        );
    }

    public function destroy(Business $business, DeleteBusiness $service): JsonResponse
    {
        $this->authorize('delete', $business);

        $service->delete($business);

        return $this->deletedResponse('Empresa excluída com sucesso');
    }

    public function approve(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update([
            'status' => \App\Enums\BusinessStatus::Active,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return $this->successResponse(
            new BusinessResource($business->fresh()),
            'Empresa aprovada com sucesso'
        );
    }

    public function suspend(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update([
            'status' => \App\Enums\BusinessStatus::Suspended
        ]);

        return $this->successResponse(
            new BusinessResource($business->fresh()),
            'Empresa suspensa com sucesso'
        );
    }

    public function activate(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update([
            'status' => \App\Enums\BusinessStatus::Active
        ]);

        return $this->successResponse(
            new BusinessResource($business->fresh()),
            'Empresa ativada com sucesso'
        );
    }

    public function deactivate(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update([
            'status' => \App\Enums\BusinessStatus::Inactive
        ]);

        return $this->successResponse(
            new BusinessResource($business->fresh()),
            'Empresa desativada com sucesso'
        );
    }

    public function assignPlan(Business $business, \Illuminate\Http\Request $request): JsonResponse
    {
        $this->authorize('update', $business);

        $request->validate([
            'platform_plan_id' => ['required', 'exists:platform_plans,id']
        ]);

        $business->update([
            'platform_plan_id' => $request->platform_plan_id
        ]);

        return $this->successResponse(
            new BusinessResource($business->load('platformPlan')),
            'Plano atribuído com sucesso'
        );
    }

    public function removePlan(Business $business): JsonResponse
    {
        $this->authorize('update', $business);

        $business->update([
            'platform_plan_id' => null
        ]);

        return $this->successResponse(
            new BusinessResource($business->fresh()),
            'Plano removido com sucesso'
        );
    }

    public function getStats(Business $business): JsonResponse
    {
        $this->authorize('view', $business);

        $stats = [
            'total_users' => $business->businessUserProfiles()->count(),
            'active_users' => $business->businessUserProfiles()->active()->count(),
            'total_customers' => $business->customers()->count(),
            'total_addresses' => $business->addresses()->count(),
            'has_active_plan' => $business->hasActivePlan(),
            'can_add_users' => $business->canAddMoreUsers(),
            'can_add_customers' => $business->canAddMoreCustomers(),
        ];

        if ($business->platformPlan) {
            $stats['plan_limits'] = [
                'max_users' => $business->platformPlan->max_users,
                'max_customers' => $business->platformPlan->max_customers,
            ];
        }

        return $this->successResponse($stats, 'Estatísticas obtidas com sucesso');
    }
}
