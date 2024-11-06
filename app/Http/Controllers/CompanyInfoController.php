<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;

class CompanyInfoController extends Controller
{

    public function index()
    {
        $companyInfos = CompanyInfo::all();
        $lastUpdated = CompanyInfo::max('updated_at');
        return response()->json([
            'companyInfos' => $companyInfos,
            'last_updated' => $lastUpdated,
        ]);
    }

    public function show($id)
    {
        $companyInfo = CompanyInfo::find($id);
        if (!$companyInfo) {
            return response()->json(['message' => 'Không tìm thấy thông tin công ty'], 404);
        }
        return response()->json($companyInfo);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'webname' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'facebook' => 'nullable|string',
            'twitter' => 'nullable|string',
            'linkedin' => 'nullable|string',
        ]);

        $companyInfo = CompanyInfo::create($validatedData);
        return response()->json($companyInfo, 201);
    }

    public function update(Request $request, $id)
    {
        $companyInfo = CompanyInfo::find($id);
        if (!$companyInfo) {
            return response()->json(['message' => 'Không tìm thấy thông tin công ty'], 404);
        }

        $validatedData = $request->validate([
            'webname' => 'string',
            'address' => 'string',
            'phone' => 'string',
            'email' => 'email',
            'facebook' => 'nullable|string',
            'twitter' => 'nullable|string',
            'linkedin' => 'nullable|string',
        ]);

        $companyInfo->update($validatedData);
        return response()->json($companyInfo);
    }

    public function destroy($id)
    {
        $companyInfo = CompanyInfo::find($id);
        if (!$companyInfo) {
            return response()->json(['message' => 'Không tìm thấy thông tin công ty'], 404);
        }

        $companyInfo->delete();
        return response()->json(['message' => 'Đã xóa thông tin công ty']);
    }
}
