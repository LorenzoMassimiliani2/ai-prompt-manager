<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\Tag;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $tags = $request->query('tags', []);            // array di id
        $view = $request->query('view', 'grid');        // grid | list | details

        $prompts = Prompt::with(['user', 'tags'])
            ->when(!$request->user(), fn($qq) => $qq->public())
            ->search($q)
            ->withAnyTags(is_array($tags) ? array_filter($tags) : [])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $allTags = Tag::orderBy('name')->get();

        return Inertia::render('Prompts/Index', [
            'prompts' => $prompts,
            'filters' => ['q' => $q, 'tags' => $tags, 'view' => $view],
            'tags' => $allTags,
            'can' => [
                'create' => (bool) $request->user(),
                'manageTags' => \Gate::allows('create', Tag::class),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Prompts/Form', ['prompt' => null,
            'allTags' => Tag::orderBy('name')->get(),
            'can' => ['manageTags' => \Gate::allows('create', Tag::class),],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'content' => 'required|string',
            'visibility' => 'required|in:private,public,unlisted',
            'tags' => 'array'
        ]);
        $data['user_id'] = $request->user()->id;

        $prompt = Prompt::create($data);
        $prompt->tags()->sync($this->syncTags($request->input('tags', [])));

        return redirect()->route('prompts.index')->with('success', 'Prompt creato!');
    }

    public function show(Prompt $prompt, Request $request)
    {
        $this->authorize('view', $prompt);
        $prompt->load(['user', 'tags']);

        $comments = $prompt->comments()
            ->with('user:id,name')
            ->paginate(10)
            ->withQueryString();
        
        $services = Service::where('is_active', true)->orderBy('sort')->get(['id','key','name','base_url','supports_query','meta']);    

        return Inertia::render('Prompts/Show', [
            'prompt' => $prompt,
            'allTags' => Tag::orderBy('name')->get(),
            'comments' => $comments,
            'services' => $services,
            'auth' => [
                'userId' => optional($request->user())->id,
                'isSuper'=> (bool) optional($request->user())->is_superuser,
            ],
            'can' => [
                'update' => (bool) $request->user()?->can('update', $prompt),
                'delete' => (bool) $request->user()?->can('delete', $prompt),
                'manageTags' => \Gate::allows('create', Tag::class),
                'commentCreate' => (bool) $request->user(), // basta login
            ],
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function edit(Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        return Inertia::render('Prompts/Form', [
            'prompt' => $prompt->load('tags'),
            'allTags' => Tag::orderBy('name')->get(),
            'can' => ['manageTags' => \Gate::allows('create', Tag::class)],
        ]);
    }

    public function update(Request $request, Prompt $prompt)
    {
        $this->authorize('update', $prompt);
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'content' => 'required|string',
            'visibility' => 'required|in:private,public,unlisted',
            'tags' => 'array'
        ]);
        $prompt->update($data);
        $prompt->tags()->sync($this->syncTags($request->input('tags', [])));

        return redirect()->route('prompts.show', $prompt)->with('success', 'Aggiornato!');
    }

    public function destroy(Prompt $prompt)
    {
        $this->authorize('delete', $prompt);
        $prompt->delete();
        return redirect()->route('prompts.index')->with('success', 'Eliminato!');
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
        $ids = [];
        $canManage = \Gate::allows('create', Tag::class);

        foreach ($tags as $t) {
            if (is_numeric($t)) {
                $ids[] = (int) $t;
                continue;
            }

            // se è una stringa (nome tag) e l'utente NON è superuser:
            if (!$canManage) {
                // ignora oppure lancia errore di validazione
                // throw \Illuminate\Validation\ValidationException::withMessages(['tags'=>'Non autorizzato a creare nuovi tag.']);
                continue;
            }

            // superuser può creare
            $tag = Tag::firstOrCreate(['slug' => \Str::slug($t)], ['name' => $t]);
            $ids[] = $tag->id;
        }
        return $ids;
    }
}
