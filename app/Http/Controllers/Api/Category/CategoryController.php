<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use App\Service\Category\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function getAll()
    {
        $result = $this->categoryService->getAllCategories();

        return response()->json($result, $result['code']);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $result = $this->categoryService->createCategory($data);

        return response()->json($result, $result['code']);
    }

    public function getById(string $id)
    {
        $result = $this->categoryService->getCategoryById($id);

        return response()->json($result, $result['code']);
    }

    public function getBySlug(string $slug)
    {
        $result = $this->categoryService->getCategoryBySlug($slug);

        return response()->json($result, $result['code']);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function delete(string $id)
    {
        $result = $this->categoryService->deleteCategory($id);

        return response()->json($result, $result['code']);
    }
}
