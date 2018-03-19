<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function getOffersByCategoryID(Request $request) {
        $categoryId = $request->categoryId;
        dd($categoryId);
    }
}
