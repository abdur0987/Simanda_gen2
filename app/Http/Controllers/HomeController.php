<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Agenda;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Jabatan;

class HomeController extends Controller
{
    /**
     * Show main dashboard.
     */
    public function index(): View
    {
        return view('pages.home.home', [
            'type_menu' => 'Home',
            'title' => 'Home',
        ]);
    }
}