<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NouveauMessageContact;
use App\Models\Contact;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150',
            'sujet' => 'required|string|min:2|max:150',
            'message' => 'required|string|min:10|max:1500',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        $contact = Contact::create($validator->validated());

        Mailer::send(config('mail.admin_email'), new NouveauMessageContact($contact));

        return response()->json(['ok' => true]);
    }
}
