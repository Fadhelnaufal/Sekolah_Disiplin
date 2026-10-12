<?php

namespace App\Http\Controllers;

use App\Models\Violation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ViolationController extends Controller
{
    public function index(Request $request)
    {
        $query = Violation::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('category', 'ilike', "%{$search}%");
        }

        $violations = $query->orderBy('category')->orderBy('points', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Violations/Index', [
            'violations' => $violations,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Violations/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:violations',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Violation::create($validated);

        return redirect()->route('violations.index')->with('success', 'Master pelanggaran berhasil ditambahkan.');
    }

    public function edit(Violation $violation)
    {
        return Inertia::render('Violations/Form', [
            'violation' => $violation
        ]);
    }

    public function update(Request $request, Violation $violation)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:violations,code,' . $violation->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'points' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $violation->update($validated);

        return redirect()->route('violations.index')->with('success', 'Master pelanggaran berhasil diperbarui.');
    }

    public function destroy(Violation $violation)
    {
        // Cek jika sudah terpakai
        if ($violation->disciplineEvents()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus pelanggaran karena sudah digunakan dalam catatan kedisiplinan. Silakan set "Tidak Aktif" alih-alih menghapus.');
        }

        $violation->delete();

        return redirect()->route('violations.index')->with('success', 'Master pelanggaran berhasil dihapus.');
    }
}
