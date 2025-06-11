<?php

namespace App\Http\Controllers;

use App\Models\PhysicalVisitor;
use App\Models\User;
use App\Services\IDCardScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PhysicalVisitorController extends Controller
{
    protected $scannerService;

    public function __construct(IDCardScannerService $scannerService)
    {
        $this->scannerService = $scannerService;
    }

    public function index(Request $request)
    {
        $query = PhysicalVisitor::with(['branch', 'hostEmployee'])
            ->when($request->status, function ($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->date, function ($q) use ($request) {
                return $q->whereDate('check_in_time', $request->date);
            })
            ->when($request->branch_id, function ($q) use ($request) {
                return $q->where('branch_id', $request->branch_id);
            })
            ->latest();

        $visitors = $query->paginate(15);
        $branches = \App\Models\Branch::orderBy('name')->get();
        $stats = PhysicalVisitor::getVisitorStats(now()->startOfDay(), now()->endOfDay());

        if ($request->wantsJson()) {
            return response()->json($visitors);
        }

        return view('visitors.index', compact('visitors', 'branches', 'stats'));
    }

    public function create()
    {
        $employees = User::employee()->active()->orderBy('name')->get();

        return view('visitors.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_card_image' => 'required|image|max:2048',
            'purpose_of_visit' => 'required|string|max:255',
            'host_employee_id' => 'nullable|exists:users,id',
            'host_department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Scan ID card
            $scanResult = $this->scannerService->scanIDCard($request->file('id_card_image'));
            dd($scanResult);
            // Store the ID card image
            $imagePath = $request->file('id_card_image')->store('visitor-id-cards', 'public');

            // Create visitor record
            $visitor = PhysicalVisitor::create([
                'branch_id' => Auth::user()->branch_id,
                'name' => $scanResult['name'],
                'date_of_birth' => $scanResult['dob'],
                'id_card_number' => $scanResult['id_number'],
                'address' => $scanResult['address'],
                'purpose_of_visit' => $request->purpose_of_visit,
                'host_employee_id' => $request->host_employee_id,
                'host_department' => $request->host_department,
                'check_in_time' => now(),
                'status' => 'checked_in',
                'id_card_image_path' => $imagePath,
                'notes' => $request->notes,
            ]);

            if ($request->wantsJson()) {
                return response()->json($visitor->load(['branch', 'hostEmployee']));
            }

            return redirect()->route('visitors.show', $visitor)
                ->with('success', 'Visitor registered successfully.');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to register visitor: '.$e->getMessage());
        }
    }

    public function show(PhysicalVisitor $visitor)
    {
        $visitor->load(['branch', 'hostEmployee']);

        if (request()->wantsJson()) {
            return response()->json($visitor);
        }

        return view('visitors.show', compact('visitor'));
    }

    public function checkout(PhysicalVisitor $visitor)
    {
        if ($visitor->status === 'checked_out') {
            return response()->json(['error' => 'Visitor already checked out'], 400);
        }

        $visitor->checkOut();

        if (request()->wantsJson()) {
            return response()->json($visitor->fresh(['branch', 'hostEmployee']));
        }

        return redirect()->route('visitors.index')
            ->with('success', 'Visitor checked out successfully.');
    }

    public function stats(Request $request)
    {
        $startDate = $request->start_date ? now()->parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? now()->parse($request->end_date) : now();

        $stats = PhysicalVisitor::getVisitorStats($startDate, $endDate);

        if ($request->wantsJson()) {
            return response()->json($stats);
        }

        return view('visitors.stats', compact('stats', 'startDate', 'endDate'));
    }
}
