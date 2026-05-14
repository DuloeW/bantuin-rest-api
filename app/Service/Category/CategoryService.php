<?php
namespace App\Service\Category;

use App\Models\Category;
use App\Traits\ServiceResponse;
use Illuminate\Support\Str;

class CategoryService
{
    use ServiceResponse;

    public function getAllCategories()
    {
        $categories = Category::all();

        return $this->successPayload($categories, 'categories retrieved successfully');
    }

    public function getCategoryById(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorPayload('category not found', [], 404);
        }

        return $this->successPayload($category, 'category retrieved successfully');
    }

    public function getCategoryBySlug(string $slug)
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return $this->errorPayload('category not found', [], 404);
        }

        return $this->successPayload($category, 'category retrieved successfully');
    }

    public function createCategory(array $data)
    {
        $category = Category::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
        ]); 

        return $this->successPayload($category, 'category created successfully', 201);
    }

    public function deleteCategory(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->errorPayload('category not found', [], 404);
        }

        $category->delete();

        return $this->successPayload(null, 'category deleted successfully');
    }
}