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

        // Albero cartelle (solo sue)
        $roots = Folder::forUser($user->id)->roots()
            ->with([
                'children' => function ($q) use ($user) {
                    $q->forUser($user->id)->with([
                        'children' => function ($qq) use ($user) {
                            $qq->forUser($user->id)->with('children'); // 3 livelli; puoi ricorsivamente serializzare lato FE
                        }
                    ]);
                }
            ])
            ->orderBy('sort')->orderBy('name')
            ->get();

        // facoltativo: “cartella corrente” via query ?folder=ID
        $currentId = (int) request('folder', 0);
        $current = $currentId ? Folder::forUser($user->id)->with('prompts.tags')->find($currentId) : null;

        // prompt dell'utente per “sposta in cartella”
        $myPrompts = Prompt::where('user_id', $user->id)->orderByDesc('updated_at')->limit(50)->get(['id', 'title']);

        return Inertia::render('Dashboard/Index', [
            'tree' => $roots,
            'current' => $current,
            'myPrompts' => $myPrompts,
        ]);
    }
}
