<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    protected function isSuper(User $u): bool { return (bool)$u->is_superuser; }

    public function viewAny(User $u): bool { return $this->isSuper($u); }
    public function view(User $u, Service $s): bool { return $this->isSuper($u); }
    public function create(User $u): bool { return $this->isSuper($u); }
    public function update(User $u, Service $s): bool { return $this->isSuper($u); }
    public function delete(User $u, Service $s): bool { return $this->isSuper($u); }
}
