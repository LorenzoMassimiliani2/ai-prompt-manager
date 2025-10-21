<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Prompt;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FolderController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', Folder::class);

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'parent_id' => 'nullable|exists:folders,id'
        ]);

        $parent = $data['parent_id'] ? Folder::findOrFail($data['parent_id']) : null;
        if ($parent) $this->authorize('view', $parent);

        [$slug,$path,$depth] = Folder::computePath($parent, $data['name']);

        $folder = Folder::create([
            'user_id'   => $request->user()->id,
            'parent_id' => $parent?->id,
            'name'      => $data['name'],
            'slug'      => $slug,
            'path'      => $path,
            'depth'     => $depth,
            'sort'      => 0,
        ]);

        return back()->with('success','Cartella creata')->with('folderId', $folder->id);
    }

    public function update(Request $request, Folder $folder)
    {
        $this->authorize('update', $folder);

        $data = $request->validate(['name'=>'required|string|max:80']);
        [$slug,$path,$depth] = Folder::computePath($folder->parent, $data['name']);

        $folder->update([
            'name'  => $data['name'],
            'slug'  => $slug,
            'path'  => $path,
            // depth invariato se non cambia parent
        ]);

        return back()->with('success','Cartella rinominata');
    }

    public function move(Request $request, Folder $folder)
    {
        $this->authorize('update', $folder);

        $data = $request->validate(['parent_id'=>'nullable|exists:folders,id']);
        $newParent = $data['parent_id'] ? Folder::findOrFail($data['parent_id']) : null;
        if ($newParent) $this->authorize('view', $newParent);

        [$slug,$path,$depth] = Folder::computePath($newParent, $folder->name);

        $folder->update([
            'parent_id' => $newParent?->id,
            'slug'      => $slug,
            'path'      => $path,
            'depth'     => $depth,
        ]);

        // TODO (facoltativo): aggiornare path/depth dei discendenti ricorsivamente

        return back()->with('success','Cartella spostata');
    }

    public function destroy(Folder $folder)
    {
        $this->authorize('delete', $folder);
        $folder->delete();
        return back()->with('success','Cartella eliminata');
    }

    public function attachPrompt(Request $request, Folder $folder, Prompt $prompt)
    {
        $this->authorize('view', $folder);
        // Solo il proprietario del prompt o superuser se la tua policy lo consente
        // (Oppure consenti di organizzare anche prompt non propri: a tua scelta)
        $folder->prompts()->syncWithoutDetaching([$prompt->id => ['user_id'=>$request->user()->id]]);
        return back()->with('success','Prompt aggiunto alla cartella');
    }

    public function detachPrompt(Request $request, Folder $folder, Prompt $prompt)
    {
        $this->authorize('view', $folder);
        $folder->prompts()->detach($prompt->id);
        return back()->with('success','Prompt rimosso dalla cartella');
    }
}
