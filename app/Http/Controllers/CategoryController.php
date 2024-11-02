<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
public function getPageOptions()
{
    $pageOptions = [
        ['value' => 'home', 'label' => 'home'],
        ['value' => 'about', 'label' => 'about'],
        ['value' => 'contact', 'label' => 'contact'],
        ['value' => 'maps', 'label' => 'maps'],
        ['value' => 'footer', 'label' => 'footer'],
    ];

    return response()->json([
        'pageOptions' => $pageOptions
    ]);
}

        // Lấy tất cả danh sách đề mục
    public function index()
    {
        $categories = Category::all();
        $lastUpdated = Category::max('updated_at'); // Lấy thời gian cập nhật cuối cùng

        return response()->json([
            'categories' => $categories,
            'last_updated' => $lastUpdated,
        ]);
    }


    // Lấy thông tin đề mục cụ thể
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:categories,code|max:255',
            'page' => 'required|in:home,about,contact,maps', // Kiểm tra giá trị cho page
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category = Category::create($request->only('name', 'code', 'page')); // Thêm trường page
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:categories,code,'.$category->id.'|max:255',
            'page' => 'required|in:home,about,contact,maps', // Kiểm tra giá trị cho page
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $category->update($request->only('name', 'code', 'page')); // Thêm trường page
        return response()->json($category, 200);
    }


    // Xóa một đề mục
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
