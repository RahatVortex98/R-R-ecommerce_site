<?php
namespace App\Filament\Widgets;
use Carbon\CarbonImmutable;
trait FiltersOrderDates {
    use \Filament\Widgets\Concerns\InteractsWithPageFilters;
    protected function dateRange($query,string $column='created_at'){
        foreach(['start','end'] as $key){$date=$this->pageFilters[$key]??null;if(is_string($date)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){
            try{$boundary=CarbonImmutable::parse($date,'Asia/Dhaka');$query->where($column,$key==='start'?'>=':'<=',($key==='start'?$boundary->startOfDay():$boundary->endOfDay())->utc());}catch(\Throwable){}
        }}return $query;
    }
}
