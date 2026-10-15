<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
class CreateOwner extends Command {
    protected $signature='app:create-owner';protected $description='Interactively create the first store owner without a preset password';
    public function handle(): int{
        $name=$this->ask('Owner name');$email=$this->ask('Owner email');$password=$this->secret('Password (at least 12 characters)');$confirm=$this->secret('Confirm password');
        $v=Validator::make(compact('name','email','password'),['name'=>'required|string|max:120','email'=>'required|email|unique:users,email','password'=>'required|string|min:12']);
        if($v->fails()||$password!==$confirm){$this->error($v->fails()?$v->errors()->first():'Passwords do not match.');return self::FAILURE;}
        User::create(['name'=>$name,'email'=>$email,'password'=>$password,'role'=>'owner']);$this->info('Owner created. Sign in at /admin.');return self::SUCCESS;
    }
}
