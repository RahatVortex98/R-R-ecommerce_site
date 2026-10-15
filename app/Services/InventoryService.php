<?php
namespace App\Services;
use App\Models\{Sku,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryService {
    public function adjust(Sku $sku,int $delta,string $reason,User $actor): void {
        if(!$delta||strlen(trim($reason))<3)throw ValidationException::withMessages(['stock'=>'Enter a nonzero adjustment and reason.']);
        DB::transaction(function()use($sku,$delta,$reason,$actor){$locked=Sku::lockForUpdate()->findOrFail($sku->id);$locked->on_hand+=$delta;$locked->save();$locked->movements()->create(['user_id'=>$actor->id,'on_hand_delta'=>$delta,'reserved_delta'=>0,'reason'=>$reason]);},3);
    }
}
