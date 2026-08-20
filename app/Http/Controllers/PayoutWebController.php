<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PayoutWebController extends Controller
{
    public function index(): View
    {
        return view('payouts.index');
    }
}
