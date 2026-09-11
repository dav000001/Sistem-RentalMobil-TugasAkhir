<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class VendorLandingController extends Controller
{
    public function show()
    {
        return view('public.vendor-landing');
    }
}
