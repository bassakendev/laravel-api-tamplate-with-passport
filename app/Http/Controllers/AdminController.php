<?php

namespace App\Http\Controllers;

use App\Models\Pub;
use App\Models\Exam;
use App\Models\User;
use App\Models\Course;
use App\Models\SalesCopy;
use App\Models\Correction;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    public function index()
    {
        return view('home');
    }
}
