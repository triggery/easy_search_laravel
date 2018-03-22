<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoriesController extends Controller
{
    public function getMainCategories() {
    	 $mainCategories = Category::whereNull('parent_id')->orderBy('position')->get()->toArray();
    	 //dd($mainCategories);
    	 return $mainCategories;
    }
}
