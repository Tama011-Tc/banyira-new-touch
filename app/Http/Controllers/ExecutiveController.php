<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Inertia\Inertia;

class ExecutiveController extends Controller
{
    public function index(ReportingService $reports)
    {
        return Inertia::render('executive/Index', ['report' => $reports->executive()]);
    }
}
