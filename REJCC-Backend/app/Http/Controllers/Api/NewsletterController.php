<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:150',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => $validator->errors()->first()], 422);
        }

        // updateOrCreate évite l'erreur de doublon si déjà inscrit.
        NewsletterSubscriber::updateOrCreate(['email' => $validator->validated()['email']], []);

        return response()->json(['ok' => true]);
    }
}
