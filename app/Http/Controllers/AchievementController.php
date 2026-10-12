<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $query = Achievement::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('level', 'ilike', "%{$search}%");
        }

        $achievements = $query->orderBy('level')->orderBy('deduction_points', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Achievements/Index', [
            'achievements' => $achievements,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Achievements/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:achievements',
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:100',
            'deduction_points' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'frequency_rule' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Achievement::create($validated);

        return redirect()->route('achievements.index')->with('success', 'Master prestasi berhasil ditambahkan.');
    }

    public function edit(Achievement $achievement)
    {
        return Inertia::render('Achievements/Form', [
            'achievement' => $achievement
        ]);
    }

    public function update(Request $request, Achievement $achievement)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:achievements,code,' . $achievement->id,
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:100',
            'deduction_points' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'frequency_rule' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $achievement->update($validated);

        return redirect()->route('achievements.index')->with('success', 'Master prestasi berhasil diperbarui.');
    }

    public function destroy(Achievement $achievement)
    {
        // Cek jika sudah terpakai
        if ($achievement->disciplineEvents()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus prestasi karena sudah digunakan dalam catatan kedisiplinan siswa. Silakan set "Tidak Aktif" alih-alih menghapus.');
        }

        $achievement->delete();

        return redirect()->route('achievements.index')->with('success', 'Master prestasi berhasil dihapus.');
    }
}
