<?php
namespace App\Policies;
use App\Models\{User,Order};
class OrderPolicy {
 public function viewAny(User $user): bool{return in_array($user->role,['owner','staff'],true);}
 public function view(User $user,Order $order): bool{return $this->viewAny($user);}
 public function update(User $user,Order $order): bool{return $this->viewAny($user);}
 public function create(User $user): bool{return false;}
 public function delete(User $user,Order $order): bool{return false;}
}
