<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Ahmad Rizal', 'employee_id' => 'EMP001', 'mac_address' => 'C1DCC29A8D6A', 'department' => 'IT'],
            ['name' => 'Siti Aminah', 'employee_id' => 'EMP002', 'mac_address' => 'AA:BB:CC:DD:EE:01', 'department' => 'HR'],
            ['name' => 'Raj Kumar', 'employee_id' => 'EMP003', 'mac_address' => 'AA:BB:CC:DD:EE:02', 'department' => 'Finance'],
            ['name' => 'Mei Ling', 'employee_id' => 'EMP004', 'mac_address' => 'AA:BB:CC:DD:EE:03', 'department' => 'Operations'],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}