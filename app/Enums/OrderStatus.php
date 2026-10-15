<?php
namespace App\Enums;
enum OrderStatus: string {
    case Pending='pending';case Confirmed='confirmed';case Dispatched='dispatched';case Delivered='delivered';case Cancelled='cancelled';case Expired='expired';case Returned='returned';
    public function allowed(): array{return match($this){self::Pending=>[self::Confirmed,self::Cancelled,self::Expired],self::Confirmed=>[self::Dispatched,self::Cancelled],self::Dispatched=>[self::Delivered,self::Returned],self::Delivered=>[self::Returned],default=>[]};}
}
