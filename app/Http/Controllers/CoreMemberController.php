<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CoreMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoreMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $data = CoreMember::where('data_state',0)
        // ->where('branch_id', Auth::user()->company_id)
        ->get();
        return view('content.CoreMember.ListCoreMember', compact('data'));
    }
    public function print() {
        // content
    }
    public function export() {
        // content
    }
}
