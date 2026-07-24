<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Testimonials\StoreTestimonial;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function store(Request $request, StoreTestimonial $storeTestimonial): RedirectResponse
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'author_title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $storeTestimonial($data, $request->file('photo'));

        return back()->with('success', 'Testimoni Anda telah dikirim dan menunggu moderasi.');
    }
}
