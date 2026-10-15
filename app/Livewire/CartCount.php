<?php
namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\On;
class CartCount extends Component {
    #[On('cart-updated')] public function refreshCount(): void{}
    public function render(){return view('livewire.cart-count',['count'=>array_sum(session('cart',[]))]);}
}
