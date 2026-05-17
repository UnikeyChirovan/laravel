<?php

namespace App\Http\Controllers;

use App\Models\FutureProject;
use Illuminate\Http\Request;

class FutureProjectController extends Controller
{
    public function index()
    {
        $futureProjects = FutureProject::all();
        return response()->json($futureProjects);
    }

    public function show($id)
    {
        $futureProject = FutureProject::find($id);
        if (!$futureProject) {
            return response()->json(['message' => 'Không tìm thấy dự án tương lai'], 404);
        }
        return response()->json($futureProject);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'timeline' => 'required|string',
            'description' => 'required|string',
        ]);

        $futureProject = FutureProject::create($validatedData);
        return response()->json($futureProject, 201);
    }

    public function update(Request $request, $id)
    {
        $futureProject = FutureProject::find($id);
        if (!$futureProject) {
            return response()->json(['message' => 'Không tìm thấy dự án'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'string',
            'timeline' => 'string',
            'description' => 'string',
        ]);

        $futureProject->update($validatedData);
        return response()->json($futureProject);
    }

    public function destroy($id)
    {
        $futureProject = FutureProject::findOrFail($id);
        $futureProject->delete();
        return response()->json(['message' => 'Xóa thành công']);
    }
}
