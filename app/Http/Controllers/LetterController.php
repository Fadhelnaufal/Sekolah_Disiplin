<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\Student;
use App\Models\Semester;
use App\Models\School;
use App\Services\LetterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LetterController extends Controller
{
    use AuthorizesRequests;

    protected LetterService $letterService;

    public function __construct(LetterService $letterService)
    {
        $this->letterService = $letterService;
    }

    public function index()
    {
        $this->authorize('viewAny', Letter::class);
        $letters = Letter::with(['student.classRoom', 'generatedBy'])->latest()->paginate(15);
        return Inertia::render('Letters/Index', [
            'letters' => $letters
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Letter::class);
        $student_id = $request->get('student_id');
        $student = null;
        if ($student_id) {
            $student = Student::with('classRoom.homeroomTeacher')->find($student_id);
        }
        $students = Student::with('classRoom')->whereIn('status', ['Active', 'active'])->get();
        return Inertia::render('Letters/Create', [
            'students' => $students,
            'selectedStudent' => $student
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Letter::class);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:notification,parent_call,call_1,call_2,call_3,counseling,statement,other',
            'subject' => 'nullable|string'
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $semester = Semester::where('status', 'Active')->firstOrFail();
        $school = School::firstOrFail();

        // Check if required signatory is empty
        if ($validated['type'] === 'call_1' && empty($school->counseling_officer_name)) {
            return back()->withErrors(['type' => 'Nama Guru BK / Koordinator BK belum diatur pada Master Data Sekolah.']);
        }
        if ($validated['type'] === 'call_2' && empty($school->vice_principal_name)) {
            return back()->withErrors(['type' => 'Nama Wakil Kepala Sekolah Bidang Kesiswaan belum diatur pada Master Data Sekolah.']);
        }
        if (in_array($validated['type'], ['call_3', 'parent_call']) && empty($school->principal_name)) {
            return back()->withErrors(['type' => 'Nama Kepala Sekolah belum diatur pada Master Data Sekolah.']);
        }

        $letterNumber = $this->letterService->generateLetterNumber($school, $validated['type']);
        $letterData = $this->letterService->buildLetterData($student, $semester, $validated['type']);
        
        $pdf = Pdf::loadView('pdf.letter', $letterData);
        $fileName = 'surat_' . strtolower($validated['type']) . '_' . $student->nis . '_' . time() . '.pdf';
        
        $filePath = 'letters/' . $fileName;
        Storage::disk('local')->put($filePath, $pdf->output());

        try {
            $letter = DB::transaction(function () use ($student, $semester, $validated, $letterNumber, $filePath, $letterData) {
                return Letter::create([
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                    'type' => $validated['type'],
                    'letter_number' => $letterNumber,
                    'subject' => $validated['subject'] ?? 'Surat Pemanggilan',
                    'generated_at' => now(),
                    'generated_by' => Auth::id(),
                    'status' => 'Generated',
                    'pdf_path' => $filePath,
                    'metadata' => [
                        'net_points' => $letterData['net_points'],
                        'sanction_level' => $letterData['sanction'] ? $letterData['sanction']->level : null,
                        'signatory_title' => $letterData['signatory_title'] ?? 'Kepala Sekolah',
                        'signatory_name' => $letterData['signatory_name'] ?? '.......................',
                    ]
                ]);
            });
        } catch (\Exception $e) {
            // Cleanup orphaned file if DB transaction fails
            if (Storage::disk('local')->exists($filePath)) {
                Storage::disk('local')->delete($filePath);
            }
            throw $e;
        }

        return redirect()->route('letters.index')->with('success', 'Surat berhasil dibuat.');
    }

    public function show(Letter $letter)
    {
        $this->authorize('view', $letter);
        $path = storage_path('app/' . $letter->pdf_path);
        
        // Fallback for legacy files that haven't been moved yet
        if (!file_exists($path)) {
            $path = storage_path('app/public/' . $letter->pdf_path);
        }
        
        if (!file_exists($path)) {
            abort(404, 'PDF file not found.');
        }
        return response()->file($path);
    }
}
