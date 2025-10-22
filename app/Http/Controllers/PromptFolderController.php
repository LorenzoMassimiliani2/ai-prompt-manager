<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptFolderController extends Controller
{
    // Aggiunge il prompt a una cartella dell'utente
    public function attach(Request $request, Prompt $prompt)
    {
        $user = $request->user();
        $request->validate([
            'folder_id' => ['required','integer','exists:folders,id'],
        ]);

        $folder = Folder::where('id', $request->folder_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // l'utente deve poter "vedere" il prompt
        $this->authorize('view', $prompt);

        // collega (idempotente)
        $folder->prompts()->syncWithoutDetaching([
            $prompt->id => ['user_id' => auth()->id()],
        ]);
        
        return back()->with('success', 'Aggiunto alla cartella!');
    }

    // Rimuove il prompt da una cartella dell'utente
    public function detach(Request $request, Prompt $prompt, Folder $folder)
    {
        $user = $request->user();

        // sicurezza: la cartella deve essere dell'utente
        abort_unless($folder->user_id === $user->id, 403);
        $this->authorize('view', $prompt);

        $folder->prompts()->detach($prompt->id);

        return back()->with('success', 'Rimosso dalla cartella!');
    }
}
