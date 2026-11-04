<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->has('action_type') && $request->input('action_type') !== '') {
            $query->where('action', $request->input('action_type'));
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->input('start_date') !== '' && $request->input('end_date') !== '') {
            $query->whereBetween('created_at', [
                $request->input('start_date').' 00:00:00',
                $request->input('end_date').' 23:59:59',
            ]);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only('search', 'action_type', 'start_date', 'end_date'),
        ]);
    }
}
