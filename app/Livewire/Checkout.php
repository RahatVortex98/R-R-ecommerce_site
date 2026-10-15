<?php
namespace App\Livewire;
use App\Services\{CartService,CheckoutService};
use App\Models\DeliveryZone;
use Livewire\Attributes\Locked;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class Checkout extends Storefront {
    public string $name='';public string $phone='';public string $address='';public string $email='';public string $note='';public ?int $delivery_zone_id=null;
    #[Locked] public string $token='';#[Locked] public string $reviewHash='';
    public function mount(): void{$this->token=session('checkout_token')??(string)\Str::uuid();session(['checkout_token'=>$this->token]);$this->delivery_zone_id=DeliveryZone::where('active',true)->value('id');$this->reviewHash=app(CartService::class)->summary($this->delivery_zone_id)['hash'];}
    public function updatedDeliveryZoneId(): void{$this->reviewHash=app(CartService::class)->summary($this->delivery_zone_id)['hash'];}
    public function submit(): mixed{
        $key='checkout:'.request()->ip();if(RateLimiter::tooManyAttempts($key,5))throw ValidationException::withMessages(['cart'=>'Please wait a minute before trying again.']);RateLimiter::hit($key,60);
        try{$order=app(CheckoutService::class)->submit(['name'=>$this->name,'phone'=>$this->phone,'address'=>$this->address,'email'=>$this->email?:null,'note'=>$this->note?:null,'delivery_zone_id'=>$this->delivery_zone_id,'review_hash'=>$this->reviewHash],session('cart',[]),$this->token);}
        catch(ValidationException $e){$this->reviewHash=app(CartService::class)->summary($this->delivery_zone_id)['hash'];throw $e;}
        session()->forget(['cart','checkout_token']);return $this->redirectRoute('order.confirmation',['reference'=>$order->public_reference]);
    }
    public function render(){return view('livewire.checkout',array_merge(app(CartService::class)->summary($this->delivery_zone_id),['zones'=>DeliveryZone::where('active',true)->get()]))->layout('layouts.app',['seo'=>$this->seo('COD checkout — R&R Care BD',noindex:true)]);}
}
