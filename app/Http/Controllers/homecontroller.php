<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index() {
        $message = "Welkom bij Laravel!";
        return view('home', compact('message'));
    }
}
