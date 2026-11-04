<?php

namespace App\Http\Controllers;

use App\Models\SanctionRule;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SanctionRuleController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('master-data.index', ['tab' => 'sanctions']);
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
        AuditService::logCreated($sanctionRule, 'Menambahkan aturan sanksi: '.$sanctionRule->sanction_name);

        return redirect()->route('master-data.index', ['tab' => 'sanctions'])->with('success', 'Aturan sanksi berhasil ditambahkan.');
    }

    public function edit(SanctionRule $sanctionRule)
    {
        return Inertia::render('SanctionRules/Form', [
            'sanctionRule' => $sanctionRule,
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

        return redirect()->route('master-data.index', ['tab' => 'sanctions'])->with('success', 'Aturan sanksi berhasil diperbarui.');
    }

    public function destroy(SanctionRule $sanctionRule)
    {
        $sanctionRule->delete();

        return redirect()->route('master-data.index', ['tab' => 'sanctions'])->with('success', 'Aturan sanksi berhasil dihapus.');
    }
}
