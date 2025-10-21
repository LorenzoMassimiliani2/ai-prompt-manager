<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommentController extends Controller
{
    public function store(Request $request, Prompt $prompt)
    {
        $this->authorize('create', Comment::class);

        $data = $request->validate([
            'body' => 'required|string|min:2|max:4000',
        ]);

        Comment::create([
            'prompt_id' => $prompt->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
        ]);

        // Torna alla show e ricarica solo i commenti
        return back()->with('success','Commento aggiunto!');
    }

    public function destroy(Prompt $prompt, Comment $comment)
    {
        $this->authorize('delete', $comment);

        // extra safety: il commento deve appartenere al prompt
        abort_unless($comment->prompt_id === $prompt->id, 404);
        $comment->delete();

        return back()->with('success','Commento eliminato!');
    }
}
