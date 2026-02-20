<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(Employee::all());
    }

    public function store(Request $request)
    {
        $employee = Employee::create($request->only('name', 'employee_id', 'mac_address', 'department'));
        return response()->json($employee, 201);
    }
}