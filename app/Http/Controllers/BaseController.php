<?php
// app/Http/Controllers/BaseController.php - For extended functionality
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return success response with data
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return error response
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): \Illuminate\Http\JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return validation error response
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Return created response
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully'): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return updated response
     */
    protected function updatedResponse($data = null, string $message = 'Resource updated successfully'): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }

    /**
     * Return deleted response
     */
    protected function deletedResponse(string $message = 'Resource deleted successfully'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message
        ], 204);
    }
}
