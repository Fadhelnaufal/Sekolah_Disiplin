<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\SanctionRule;
use App\Models\Violation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'violations');

        $violations = Violation::orderBy('code')->get();
        $achievements = Achievement::orderBy('code')->get();
        $sanctionRules = SanctionRule::orderBy('level')->get();

        return Inertia::render('MasterData/Index', [
            'violations' => $violations,
            'achievements' => $achievements,
            'sanctionRules' => $sanctionRules,
            'current_tab' => $tab,
        ]);
    }
}
