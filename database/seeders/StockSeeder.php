<?php

namespace Database\Seeders;

use App\Models\{StockItem,Trial,User};
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $operator=User::where('role','admin')->first();
        $items=[
            ['CLX 7702','variety','E00002',20,96,99,200,55],['NUN 8812','variety','E00004',20,94,98,100,12],
            ['RZ 24-118','variety','E00001',20,92,99,80,0],['SYN 3391','variety','E00003',20,90,97,70,15],
            ['Magenta','control','T00045',30,95,99,240,25],['Novitus','control','T00030',30,93,98,120,80],
        ];
        foreach($items as $index=>$row){[$name,$kind,$ref,$threshold,$germ,$purity,$received,$remaining]=$row;$item=StockItem::updateOrCreate(['name'=>$name,'kind'=>$kind],['ref_code'=>$ref,'unit'=>'graines','alert_threshold'=>$threshold,'germination_pct'=>$germ,'purity_pct'=>$purity,'expiry_date'=>now()->addMonths($index===3?1:10),'last_germ_test_on'=>$index===4?now()->subMonths(14):now()->subMonth(),'is_archived'=>false]);$item->movements()->delete();$item->lots()->delete();$lot=$item->lots()->create(['code'=>'LOT-26-'.str_pad((string)($index+1),3,'0',STR_PAD_LEFT),'supplier_name'=>'Semences Démo','received_on'=>now()->subMonths(2),'expiry_date'=>now()->addMonths($index===3?1:10),'germination_pct'=>$germ,'purity_pct'=>$purity,'last_germ_test_on'=>$index===4?now()->subMonths(14):now()->subMonth(),'location'=>'Magasin A']);$item->movements()->create(['stock_lot_id'=>$lot->id,'moved_on'=>now()->subMonths(2),'type'=>'in','quantity'=>$received,'reason'=>'achat','operator_id'=>$operator?->id,'reference'=>'RECEPTION-'.$lot->code,'lot_number'=>$lot->code]);if($received>$remaining){$trial=Trial::orderBy('id')->skip($index%6)->first();$item->movements()->create(['stock_lot_id'=>$lot->id,'moved_on'=>now()->subWeeks(3),'type'=>'out','quantity'=>$received-$remaining,'reason'=>'semis','trial_id'=>$trial?->id,'trial_code'=>$trial?->code,'operator_id'=>$operator?->id,'reference'=>'SEMIS-'.$trial?->code,'lot_number'=>$lot->code]);}}
    }
}
