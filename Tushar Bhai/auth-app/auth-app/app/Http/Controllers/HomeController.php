<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class HomeController extends Controller
{
    public function index()
    {
        $data['companies'] = Company::all();

        return view('home', $data);
    }
}
