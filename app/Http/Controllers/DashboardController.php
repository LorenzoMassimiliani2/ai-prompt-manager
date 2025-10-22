<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Tutte le cartelle dell’utente (flat) + count dei prompt diretti di QUELL’utente
        $folders = Folder::forUser($user->id)
            ->withCount([
                'prompts as direct_prompts_count' => function ($q) use ($user) {
                    $q->where('folder_prompt.user_id', $user->id);
                }
            ])
            ->orderBy('sort')->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'user_id', 'sort']);

        // Cartella corrente (selezionata) con i prompt
        $currentId = (int) $request->query('folder', 0);
        $current = $currentId
            ? Folder::forUser($user->id)
                ->with('prompts.tags')
                ->withCount([
                    'prompts as direct_prompts_count' => function ($q) use ($user) {
                        $q->where('folder_prompt.user_id', $user->id);
                    }
                ])
                ->find($currentId)
            : null;

        $myPrompts = Prompt::where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title']);


        return Inertia::render('Dashboard/Index', [
            'folders' => $folders,  // <— flat
            'current' => $current,
            'myPrompts' => $myPrompts,
        ]);
    }
}
