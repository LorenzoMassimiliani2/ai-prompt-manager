<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;

class PromptPolicy
{
    public function view(?User $user, Prompt $prompt): bool {
        if ($prompt->visibility === 'public') return true;
        if ($prompt->visibility === 'unlisted') return true; // link diretto
        return $user?->id === $prompt->user_id;
    }

    public function update(User $user, Prompt $prompt): bool {
        return $user->id === $prompt->user_id;
    }

    public function delete(User $user, Prompt $prompt): bool {
        return $user->id === $prompt->user_id;
    }
}
