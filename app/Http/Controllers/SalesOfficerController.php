<?php

namespace App\Http\Controllers;

use App\Models\SalesOfficer;
use Illuminate\Http\Request;

class SalesOfficerController extends Controller
{
    public function index()
    {
        $officers = SalesOfficer::latest()->get();
        return view('admin_panel.sales_officer.index', compact('officers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_urdu' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:50',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'sale_commission_rate' => 'nullable|numeric|min:0|max:100',
            'profit_commission_rate' => 'nullable|numeric|min:0|max:100',
            'edit_id' => 'nullable|integer|exists:sales_officers,id',
        ]);

        $saleCommRate = isset($validated['sale_commission_rate']) 
            ? (float)$validated['sale_commission_rate'] 
            : (isset($validated['commission_rate']) ? (float)$validated['commission_rate'] : 0.00);
        $profitCommRate = isset($validated['profit_commission_rate']) ? (float)$validated['profit_commission_rate'] : 0.00;

        $officerData = [
            'name' => $validated['name'],
            'name_urdu' => $validated['name_urdu'] ?? null,
            'mobile' => $validated['mobile'],
            'commission_rate' => $saleCommRate, // maintain backward compatibility
            'sale_commission_rate' => $saleCommRate,
            'profit_commission_rate' => $profitCommRate,
        ];

        if (!empty($request->edit_id)) {
            $officer = SalesOfficer::findOrFail($request->edit_id);
            $officer->update($officerData);
            $message = 'Sales Officer updated successfully.';
        } else {
            $officer = SalesOfficer::create($officerData);
            $message = 'Sales Officer created successfully.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $officer,
            ]);
        }

        return redirect()->route('sales.officer.index')->with('success', $message);
    }

    public function edit($id)
    {
        $officer = SalesOfficer::findOrFail($id);
        return response()->json($officer);
    }

    public function destroy(Request $request, $id)
    {
        $officer = SalesOfficer::findOrFail($id);
        $officer->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales Officer deleted successfully.',
            ]);
        }

        return redirect()->route('sales.officer.index')->with('success', 'Sales Officer deleted successfully.');
    }
}
