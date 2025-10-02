<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'title' => 'Velkommen til Reportmaker 🚀',
            'message' => 'Du er nå inne i din egen Laravel-app og kan bygge videre.'
        ]);
    }
}
