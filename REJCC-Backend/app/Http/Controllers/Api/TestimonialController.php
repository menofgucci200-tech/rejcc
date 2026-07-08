<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'testimonials' => Testimonial::orderBy('ordre')->get()]);
    }
}
