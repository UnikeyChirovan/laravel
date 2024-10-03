<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 400);
        }
        $contact = new Contact();
        $contact->username = $request->username;
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->title = $request->title;
        $contact->message = strip_tags($request->message);
        $contact->contacted_at = now(); 
        $contact->save();
        $this->sendContactEmail($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Liên hệ đã được gửi thành công!'
        ], 200);
    }
    private function sendContactEmail($data)
    {
        Mail::send([], [], function ($message) use ($data) {
            $message->to('selorson.tcv@gmail.com') 
                ->subject($data['title']) 
                ->html('
                    <h2>Thông tin liên hệ</h2>
                    <p><strong>Họ tên:</strong> ' . $data['name'] . '</p>
                    <p><strong>Email:</strong> ' . $data['email'] . '</p>
                    <p><strong>Username:</strong> ' . ($data['username'] ?? 'Không có') . '</p>
                    <p><strong>Vấn đề:</strong> ' . $data['title'] . '</p>
                    <p><strong>Nội dung:</strong></p>
                    <p>' . nl2br($data['message']) . '</p>
                '); 
        });
    }
    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->has('start_date')) {
            $query->where('contacted_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('contacted_at', '<=', $request->end_date);
        }

        $contacts = $query->get();

        return response()->json($contacts);
    }
}
