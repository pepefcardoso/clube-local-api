<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlatformPlan\FilterPlatformPlansRequest;
use App\Http\Requests\PlatformPlan\StorePlatformPlanRequest;
use App\Http\Requests\PlatformPlan\UpdatePlatformPlanRequest;
use App\Http\Resources\PlatformPlanResource;
use App\Services\PlatformPlan\CreatePlatformPlan;
use App\Services\PlatformPlan\UpdatePlatformPlan;
use App\Services\PlatformPlan\DeletePlatformPlan;
use App\Services\PlatformPlan\ListPlatformPlans;
use App\Models\PlatformPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlatformPlanController extends BaseController
{
    public function index(FilterPlatformPlansRequest $request, ListPlatformPlans $service): AnonymousResourceCollection
    {
        $plans = $service->list($request->validated());
        return PlatformPlanResource::collection($plans);
    }

    public function store(StorePlatformPlanRequest $request, CreatePlatformPlan $service): JsonResponse
    {
        $this->authorize('create', PlatformPlan::class);

        $plan = $service->create($request->validated());

        return $this->createdResponse(
            new PlatformPlanResource($plan),
            'Plano criado com sucesso'
        );
    }

    public function show(PlatformPlan $platformPlan): PlatformPlanResource
    {
        $this->authorize('view', $platformPlan);
        return new PlatformPlanResource($platformPlan);
    }

    public function update(UpdatePlatformPlanRequest $request, PlatformPlan $platformPlan, UpdatePlatformPlan $service): JsonResponse
    {
        $this->authorize('update', $platformPlan);

        $plan = $service->update($platformPlan, $request->validated());

        return $this->updatedResponse(
            new PlatformPlanResource($plan),
            'Plano atualizado com sucesso'
        );
    }

    public function destroy(PlatformPlan $platformPlan, DeletePlatformPlan $service): JsonResponse
    {
        $this->authorize('delete', $platformPlan);
        $service->delete($platformPlan);

        return $this->deletedResponse('Plano excluído com sucesso');
    }

    public function activate(PlatformPlan $platformPlan): JsonResponse
    {
        $this->authorize('update', $platformPlan);

        $platformPlan->update(['is_active' => true]);

        return $this->successResponse(
            new PlatformPlanResource($platformPlan),
            'Plano ativado com sucesso'
        );
    }

    public function deactivate(PlatformPlan $platformPlan): JsonResponse
    {
        $this->authorize('update', $platformPlan);

        $platformPlan->update(['is_active' => false]);

        return $this->successResponse(
            new PlatformPlanResource($platformPlan),
            'Plano desativado com sucesso'
        );
    }

    public function toggleFeatured(PlatformPlan $platformPlan): JsonResponse
    {
        $this->authorize('update', $platformPlan);

        $platformPlan->update(['is_featured' => !$platformPlan->is_featured]);

        $message = $platformPlan->is_featured ? 'Plano marcado como destaque' : 'Plano removido dos destaques';

        return $this->successResponse(
            new PlatformPlanResource($platformPlan),
            $message
        );
    }

    public function publicPlans(): AnonymousResourceCollection
    {
        $plans = PlatformPlan::active()->ordered()->get();
        return PlatformPlanResource::collection($plans);
    }
}
