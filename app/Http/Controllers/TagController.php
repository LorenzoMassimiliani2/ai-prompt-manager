<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct()
    {
        // mappa automaticamente CRUD ⇄ policy
        $this->authorizeResource(Tag::class, 'tag');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $tag = Tag::firstOrCreate(
            ['slug'=>\Str::slug($data['name'])],
            ['name'=>$data['name']]
        );
        return back()->with('success','Tag creato')->with('createdTagId',$tag->id);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate(['name'=>'required|string|max:60']);
        $tag->update(['name'=>$data['name'], 'slug'=>\Str::slug($data['name'])]);
        return back()->with('success','Tag aggiornato');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return back()->with('success','Tag eliminato');
    }
}
