<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function index()
    {
        // Must have bulk import rights
        if (!auth()->user()->hasAnyRole(['Super Admin', 'Tata Tertib', 'BK'])) {
            abort(403);
        }
        
        return Inertia::render('Imports/Index', [
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'semesters' => Semester::orderByDesc('start_date')->get(),
        ]);
    }

    public function upload(Request $request, ExcelImportService $service)
    {
        // Enforce RBAC
        $user = auth()->user();
        $sheet = $request->input('sheet');
        
        $allowed = false;
        if ($user->hasRole('Super Admin')) $allowed = true;
        elseif ($user->hasRole('Tata Tertib') && in_array($sheet, ['DATA_SISWA', 'CATATAN', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI'])) $allowed = true;
        elseif ($user->hasRole('BK') && in_array($sheet, ['PEMBINAAN', 'CATATAN'])) $allowed = true;
        
        if (!$allowed) {
            abort(403, "Anda tidak memiliki izin mengimpor sheet $sheet.");
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'sheet' => 'required|string',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        $file = $request->file('file');
        
        // Store the file temporarily
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('imports', $filename);
        $fullPath = Storage::path($path);

        $options = $request->only(['academic_year_id', 'semester_id']);
        
        $previewResult = $service->parseAndPreview($fullPath, $sheet, $options);
        
        // Cache preview state with a unique ID
        $previewId = (string) Str::uuid();
        
        Cache::put("import_preview_{$previewId}", [
            'file_path' => $fullPath,
            'sheet' => $sheet,
            'options' => $options,
            'stats' => $previewResult['stats'],
            'user_id' => $user->id,
        ], now()->addHours(2));

        return response()->json([
            'preview_id' => $previewId,
            'stats' => $previewResult['stats'],
            'errors' => $previewResult['errors'],
            'preview_rows' => $previewResult['preview'],
        ]);
    }

    public function commit(Request $request, ExcelImportService $service)
    {
        $request->validate([
            'preview_id' => 'required|string',
        ]);
        
        $previewId = $request->input('preview_id');
        $cacheKey = "import_preview_{$previewId}";
        $previewData = Cache::get($cacheKey);
        
        if (!$previewData) {
            return response()->json(['error' => 'Sesi preview telah kedaluwarsa atau tidak valid.'], 400);
        }
        
        if ($previewData['user_id'] !== auth()->id()) {
            return response()->json(['error' => 'Tidak sah mengonfirmasi import milik pengguna lain.'], 403);
        }
        
        $fullPath = $previewData['file_path'];
        
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File import sudah terhapus dari server.'], 400);
        }

        try {
            $result = $service->commit($fullPath, $previewData['sheet'], $previewData['options']);
            
            // Cleanup
            Storage::delete('imports/' . basename($fullPath));
            Cache::forget($cacheKey);
            
            \App\Services\AuditService::log('imported', "Melakukan import data {$previewData['sheet']}", null, [], [], [
                'type' => $previewData['sheet'],
                'rows_imported' => $result
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Import berhasil diselesaikan.',
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Kegagalan sistem saat commit: ' . $e->getMessage()], 500);
        }
    }
}
