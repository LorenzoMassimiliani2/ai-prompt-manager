<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    protected function isOwner(User $user, Comment $comment): bool {
        return $user->id === $comment->user_id;
    }

    public function create(User $user): bool { return true; } // basta essere autenticati

    public function delete(User $user, Comment $comment): bool {
        return $this->isOwner($user, $comment);
    }
}
