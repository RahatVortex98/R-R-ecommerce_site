<?php
namespace App\Policies;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
class CatalogPolicy {
 public function viewAny(User $user): bool{return in_array($user->role,['owner','staff'],true);}
 public function view(User $user,Model $model): bool{return $this->viewAny($user);}
 public function create(User $user): bool{return $this->viewAny($user);}
 public function update(User $user,Model $model): bool{return $this->viewAny($user);}
 public function delete(User $user,Model $model): bool{return $this->viewAny($user);}
 public function restore(User $user,Model $model): bool{return $this->viewAny($user);}
 public function forceDelete(User $user,Model $model): bool{return false;}
 public function deleteAny(User $user): bool{return false;}
}
