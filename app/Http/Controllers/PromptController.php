<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $prompts = Prompt::with(['user','tags'])
            ->when(!$request->user(), fn($qq)=>$qq->public())
            ->search($q)
            ->latest()->paginate(12)->withQueryString();

        $tags = Tag::orderBy('name')->take(50)->get();

        return Inertia::render('Prompts/Index', [
            'prompts' => $prompts,
            'filters' => ['q' => $q],
            'tags'    => $tags
        ]);
    }

    public function create() {
        return Inertia::render('Prompts/Form', ['prompt' => null, 'allTags' => Tag::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'=>'required|string|max:160',
            'content'=>'required|string',
            'visibility'=>'required|in:private,public,unlisted',
            'tags'=>'array'
        ]);
        $data['user_id'] = $request->user()->id;

        $prompt = Prompt::create($data);
        $prompt->tags()->sync($this->syncTags($request->input('tags', [])));

        return redirect()->route('prompts.index')->with('success','Prompt creato!');
    }

    public function show(Prompt $prompt)
    {
        $this->authorize('view', $prompt);
        $prompt->load(['user','tags']);
        return Inertia::render('Prompts/Show', ['prompt' => $prompt]);
    }

    public function edit(Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        return Inertia::render('Prompts/Form', [
            'prompt' => $prompt->load('tags'),
            'allTags' => Tag::orderBy('name')->get()
        ]);
    }

    public function update(Request $request, Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        $data = $request->validate([
            'title'=>'required|string|max:160',
            'content'=>'required|string',
            'visibility'=>'required|in:private,public,unlisted',
            'tags'=>'array'
        ]);
        $prompt->update($data);
        $prompt->tags()->sync($this->syncTags($request->input('tags', [])));

        return redirect()->route('prompts.show', $prompt)->with('success','Aggiornato!');
    }

    public function destroy(Prompt $prompt)
    {
        $this->authorize('delete', $prompt);
        $prompt->delete();
        return redirect()->route('prompts.index')->with('success','Eliminato!');
    }

    public function toggleFavorite(Request $request, Prompt $prompt)
    {
        $user = $request->user();
        if ($prompt->favoriters()->where('user_id', $user->id)->exists()) {
            $prompt->favoriters()->detach($user->id);
        } else {
            $prompt->favoriters()->attach($user->id);
        }
        return back();
    }

    private function syncTags(array $tags): array
    {
        // accetta array di id o nomi, crea i mancanti
        $ids = [];
        foreach ($tags as $t) {
            if (is_numeric($t)) { $ids[] = (int)$t; continue; }
            $tag = Tag::firstOrCreate(['slug'=>\Str::slug($t)], ['name'=>$t]);
            $ids[] = $tag->id;
        }
        return $ids;
    }
}
