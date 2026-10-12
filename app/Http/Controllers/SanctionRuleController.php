<?php

namespace App\Http\Controllers;

use App\Models\SanctionRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SanctionRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = SanctionRule::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('level', 'ilike', "%{$search}%")
                  ->orWhere('action', 'ilike', "%{$search}%")
                  ->orWhere('executor', 'ilike', "%{$search}%");
        }

        $sanctionRules = $query->orderBy('min_points', 'asc')->paginate(15)->withQueryString();

        return Inertia::render('SanctionRules/Index', [
            'sanctionRules' => $sanctionRules,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('SanctionRules/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'required|string|max:50',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'required|integer|gte:min_points',
            'action' => 'required|string|max:255',
            'executor' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $sanctionRule = SanctionRule::create($validated);
        \App\Services\AuditService::logCreated($sanctionRule, 'Menambahkan aturan sanksi: ' . $sanctionRule->sanction_name);

        return redirect()->route('sanction-rules.index')->with('success', 'Aturan sanksi berhasil ditambahkan.');
    }

    public function edit(SanctionRule $sanctionRule)
    {
        return Inertia::render('SanctionRules/Form', [
            'sanctionRule' => $sanctionRule
        ]);
    }

    public function update(Request $request, SanctionRule $sanctionRule)
    {
        $validated = $request->validate([
            'level' => 'required|string|max:50',
            'min_points' => 'required|integer|min:0',
            'max_points' => 'required|integer|gte:min_points',
            'action' => 'required|string|max:255',
            'executor' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $sanctionRule->update($validated);

        return redirect()->route('sanction-rules.index')->with('success', 'Aturan sanksi berhasil diperbarui.');
    }

    public function destroy(SanctionRule $sanctionRule)
    {
        $sanctionRule->delete();

        return redirect()->route('sanction-rules.index')->with('success', 'Aturan sanksi berhasil dihapus.');
    }
}
