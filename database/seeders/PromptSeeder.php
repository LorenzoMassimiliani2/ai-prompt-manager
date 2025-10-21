<?php

namespace Database\Seeders;

use App\Models\Prompt;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create(['email'=>'demo@example.com']);
        $tags = collect(['copywriting','marketing','developer','design'])
            ->map(fn($n) => Tag::firstOrCreate(['slug'=>\Str::slug($n)], ['name'=>$n]))
            ->pluck('id')
            ->all();

        for ($i=1; $i<=8; $i++){
            $p = Prompt::create([
                'user_id'=>$user->id,
                'title'=>"Prompt di esempio #$i",
                'content'=>"Agisci come esperto di $i. Fornisci 3 punti chiave e 2 esempi pratici.\nUsa tono chiaro e conciso.",
                'visibility'=> $i%3===0 ? 'public' : 'private',
                'meta'=>['model'=>'gpt-4o','lang'=>'it'], 
                'slug' => 'prompt-di-esempio-' . $i . '-' . \Str::random(6),
            ]);
            $p->tags()->sync(array_rand(array_flip($tags), rand(1,3)));
        }
    }
}
