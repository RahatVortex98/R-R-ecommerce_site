<?php
namespace App\Policies;
use App\Models\User;
class UserPolicy {
 public function viewAny(User $user): bool{return $user->role==='owner';}
 public function view(User $user,User $record): bool{return $this->viewAny($user);}
 public function create(User $user): bool{return $this->viewAny($user);}
 public function update(User $user,User $record): bool{return $this->viewAny($user);}
 public function delete(User $user,User $record): bool{return $this->viewAny($user)&&$user->id!==$record->id&&($record->role!=='owner'||User::where('role','owner')->count()>1);}
}
