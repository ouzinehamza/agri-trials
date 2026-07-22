<?php
namespace App\Http\Controllers;
use App\Domain\Reporting\ReportingService;
use Illuminate\Http\Request;
use Inertia\{Inertia,Response};
class DashboardController extends Controller { public function __construct(private ReportingService $reports){} public function index(Request $request):Response{return Inertia::render('Dashboard',$this->reports->dashboard($request));} }
