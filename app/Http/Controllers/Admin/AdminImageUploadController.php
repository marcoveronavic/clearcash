<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminImageUploadController extends Controller
{
    public function upload(Request $request) {
        try {
            $image = $request->file('file');
            $path = $image->store('uploads', 'public');

            $location = asset("storage/$path");

            return response()->json(['location' => $location]);
        }
        catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
