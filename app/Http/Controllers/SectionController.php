<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionController extends Controller
{
    public function createSection(Request $request)
    {
        $request->validate([
            'section_number' => 'required|integer',
            'title' => 'required|string',
            'content' => 'required|array',
        ]);

        $filename = 'section-' . $request->input('section_number') . '.txt';
        $path = 'contents/' . $filename;

        // Kiểm tra file đã tồn tại chưa
        if (Storage::exists($path)) {
            return response()->json(['message' => 'File đã tồn tại. Vui lòng chọn số thứ tự khác.'], 409);
        }

        // Lưu nội dung vào file
        $fullContent = implode("\n\n", $request->input('content'));
        Storage::put($path, $fullContent);

        // Tạo mới section
        $section = Section::create([
            'title' => $request->input('title'),
            'section_number' => $request->input('section_number'),
            'file_path' => $path,
        ]);

        return response()->json($section, 201);
    }

    public function updateSection(Request $request, $id)
    {
        $request->validate([
            'section_number' => 'required|integer',
            'title' => 'required|string',
            'content' => 'required|array',
        ]);

        $section = Section::findOrFail($id);
        $filename = 'section-' . $request->input('section_number') . '.txt';
        $path = 'contents/' . $filename;

        // Nếu file khác với file cũ và file mới đã tồn tại, trả về lỗi
        if ($section->file_path !== $path && Storage::exists($path)) {
            return response()->json(['message' => 'File đã tồn tại. Vui lòng chọn số thứ tự khác.'], 409);
        }

        // Xóa file cũ nếu đường dẫn đã thay đổi
        if ($section->file_path !== $path) {
            Storage::delete($section->file_path);
        }

        // Lưu file mới
        $fullContent = implode("\n\n", $request->input('content'));
        Storage::put($path, $fullContent);

        // Cập nhật thông tin section
        $section->update([
            'title' => $request->input('title'),
            'section_number' => $request->input('section_number'),
            'file_path' => $path,
        ]);

        return response()->json($section, 200);
    }

    public function getSection($id)
    {
        $section = Section::findOrFail($id);

        if (Storage::exists($section->file_path)) {
            $content = Storage::get($section->file_path);
            $section->content = explode("\n\n", $content);
        } else {
            $section->content = [];
        }

        return response()->json($section);
    }

    public function index()
    {
        return Section::all();
    }

    public function destroy($id)
    {
        $section = Section::findOrFail($id);

        if ($section->file_path) {
            Storage::delete($section->file_path);
        }

        $section->delete();

        return response()->json(null, 204);
    }
}
