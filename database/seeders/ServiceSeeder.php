<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['key'=>'chatgpt','name'=>'ChatGPT','base_url'=>'https://chat.openai.com/','supports_query'=>false,'sort'=>10,
             'meta'=>['icon_path'=>'M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z','viewBox'=>'0 0 24 24']],
            ['key'=>'gemini','name'=>'Gemini','base_url'=>'https://gemini.google.com/app','supports_query'=>false,'sort'=>20,
             'meta'=>['icon_path'=>'M4 12a8 8 0 1 0 16 0','viewBox'=>'0 0 24 24']],
            ['key'=>'claude','name'=>'Claude','base_url'=>'https://claude.ai/new','supports_query'=>false,'sort'=>30,
             'meta'=>['icon_path'=>'M3 12h18','viewBox'=>'0 0 24 24']],
            ['key'=>'perplexity','name'=>'Perplexity','base_url'=>'https://www.perplexity.ai/search','supports_query'=>true,'sort'=>40,
             'meta'=>['icon_path'=>'M12 3l9 9-9 9-9-9Z','viewBox'=>'0 0 24 24']],
            ['key'=>'copilot','name'=>'Copilot','base_url'=>'https://copilot.microsoft.com/','supports_query'=>false,'sort'=>50,
             'meta'=>['icon_path'=>'M4 4h16v16H4z','viewBox'=>'0 0 24 24']],
            ['key'=>'poe','name'=>'Poe','base_url'=>'https://poe.com/','supports_query'=>false,'sort'=>60,
             'meta'=>['icon_path'=>'M12 5l7 7-7 7-7-7z','viewBox'=>'0 0 24 24']],
        ];

        foreach ($items as $it) {
            Service::updateOrCreate(['key'=>$it['key']], $it);
        }
    }
}
