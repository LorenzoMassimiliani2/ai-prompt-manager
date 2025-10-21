<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    public function viewAny(User $u): bool { return true; } // dashboard privata
    public function view(User $u, Folder $f): bool { return $f->user_id === $u->id; }
    public function create(User $u): bool { return true; }
    public function update(User $u, Folder $f): bool { return $f->user_id === $u->id; }
    public function delete(User $u, Folder $f): bool { return $f->user_id === $u->id; }
}
