<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * List attendance records with optional filters.
     */
    public function index(Request $request)
    {
        $query = Attendance::with('employee')
            ->orderBy('date', 'desc');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('month')) {
            // format: YYYY-MM
            $query->whereYear('date', substr($request->month, 0, 4))
                  ->whereMonth('date', substr($request->month, 5, 2));
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->paginate(50);

        return response()->json(['success' => true, 'data' => $records]);
    }

    /**
     * Record attendance for one or multiple employees (bulk).
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'check_in'    => 'nullable|date_format:H:i',
            'check_out'   => 'nullable|date_format:H:i',
            'status'      => 'required|in:present,absent,late,leave,holiday',
            'notes'       => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::updateOrCreate(
            [
                'tenant_id'   => config('app.current_tenant_id'),
                'employee_id' => $request->employee_id,
                'date'        => $request->date,
            ],
            [
                'check_in'  => $request->check_in,
                'check_out' => $request->check_out,
                'status'    => $request->status,
                'notes'     => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil dicatat',
            'data'    => $attendance->load('employee'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $attendance = Attendance::findOrFail($id);

        $request->validate([
            'check_in'  => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status'    => 'required|in:present,absent,late,leave,holiday',
            'notes'     => 'nullable|string|max:500',
        ]);

        $attendance->update($request->only(['check_in', 'check_out', 'status', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Absensi berhasil diperbarui',
            'data'    => $attendance->load('employee'),
        ]);
    }

    public function destroy(string $id)
    {
        Attendance::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Absensi dihapus']);
    }

    /**
     * Monthly attendance summary per employee.
     */
    public function monthlySummary(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $employees = Employee::active()->get();

        $summary = $employees->map(function ($emp) use ($year, $mon) {
            $records = Attendance::where('employee_id', $emp->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $mon)
                ->get();

            return [
                'employee_id'   => $emp->id,
                'employee_name' => $emp->name,
                'position'      => $emp->position,
                'present'       => $records->where('status', 'present')->count(),
                'late'          => $records->where('status', 'late')->count(),
                'absent'        => $records->where('status', 'absent')->count(),
                'leave'         => $records->where('status', 'leave')->count(),
                'holiday'       => $records->where('status', 'holiday')->count(),
                'total_days'    => $records->count(),
                'work_hours'    => $records->sum(fn($r) => $r->work_hours ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'month'   => $month,
                'summary' => $summary,
            ],
        ]);
    }
}
