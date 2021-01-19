<?php

namespace ShopEM\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;


class ImportTest implements ToCollection
{

    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->params = $params;
    }

    public function collection(Collection $rows)
    {
        //如果需要去除表头
        unset($rows[0]);
        //$rows 是数组格式
        $this->createData($rows);
    }

    /**
     * 数据存储
     * @Author hfh_wind
     * @param $rows
     */
    public function createData($rows)
    {


        foreach ($rows as $row)
        {
//
//            $sql = "SELECT  *   from  em_goods_import_details  where goods_name='".$row[1]."'   group by gc_2";
//
//            $son = DB::select($sql);
            DB::table('goods_import_details')->where('goods_name','=',$row[1])->update(['tx'=>$row[7],'goods_serial'=>$row[6]]);
//       DB::update("update em_goods_import_details set tx='".$row[7]."',goods_serial= '".$row[6]."'   where goods_name= ?", ["'".$row[1]."'"]);

        }
        return true;
    }
}
