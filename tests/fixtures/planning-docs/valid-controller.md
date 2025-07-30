# Controller Planning Document

File: app/Http/Controllers/TestController.php

## Overview
This planning document demonstrates the expected format for controller generation.

## Dependencies
- Laravel HTTP Foundation
- Custom validation rules
- Service layer integration

## Methods

### Index Method
- Returns paginated list of resources
- Supports filtering and sorting
- Includes eager loading for performance

### Store Method
- Validates incoming request data
- Creates new resource via service layer
- Returns JSON response with created resource

### Show Method
- Retrieves single resource by ID
- Includes related data
- Handles not found cases

### Update Method
- Validates partial updates
- Updates resource via service layer
- Returns updated resource

### Destroy Method
- Soft deletes resource
- Checks for dependencies
- Returns success response

## Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\TestRequest;
use App\Services\TestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function __construct(
        private TestService $testService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'category']);
        $perPage = $request->integer('per_page', 15);
        
        $results = $this->testService->paginate($filters, $perPage);
        
        return response()->json($results);
    }

    public function store(TestRequest $request): JsonResponse
    {
        $data = $request->validated();
        $resource = $this->testService->create($data);
        
        return response()->json($resource, 201);
    }

    public function show(int $id): JsonResponse
    {
        $resource = $this->testService->findOrFail($id);
        
        return response()->json($resource);
    }

    public function update(TestRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $resource = $this->testService->update($id, $data);
        
        return response()->json($resource);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->testService->delete($id);
        
        return response()->json(['message' => 'Resource deleted successfully']);
    }
}
```