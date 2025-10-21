<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    // se usi il flag is_superuser
    protected function isSuper(User $user): bool { return (bool) $user->is_superuser; }

    public function viewAny(User $user): bool   { return $this->isSuper($user); }
    public function view(User $user, Tag $tag): bool { return $this->isSuper($user); }
    public function create(User $user): bool    { return $this->isSuper($user); }
    public function update(User $user, Tag $tag): bool { return $this->isSuper($user); }
    public function delete(User $user, Tag $tag): bool { return $this->isSuper($user); }

    // facoltative
    public function restore(User $user, Tag $tag): bool { return $this->isSuper($user); }
    public function forceDelete(User $user, Tag $tag): bool { return $this->isSuper($user); }
}
