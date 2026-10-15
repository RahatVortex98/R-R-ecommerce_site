<?php
namespace App\Mail;
use App\Models\Order;
use Illuminate\Mail\Mailable;
class OrderConfirmation extends Mailable {
    public function __construct(public Order $order){}
    public function build(){return $this->subject('R&R Care BD — COD order received')->view('mail.order-confirmation');}
}
