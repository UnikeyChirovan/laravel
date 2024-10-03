<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    public function getInfo()
    {
        // Thông tin công ty cố định
        $companyInfo = [
            'webname' => 'SELORSON TALES INTERTAINMENT',
            'address' => '123 Đường ABC, Quận 1, TP.HCM',
            'phone' => '0123 456 789',
            'email' => 'contact@example.com',
            'facebook' => 'https://www.facebook.com/truyencaoviet.vn',
            'twitter' => 'https://twitter.com',
            'linkedin' => 'https://linkedin.com',
        ];

        return response()->json($companyInfo);
    }
}
