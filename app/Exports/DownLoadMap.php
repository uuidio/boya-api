<?php

namespace ShopEM\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use ShopEM\Models\UserAccount;

class DownLoadMap implements FromCollection
{
    public $data;


    public function __construct(array $data)
    {
        $this->data = $data;
    }



    public function collection()
    {

        //return collect($this->data);
        $data = $this->data;
        foreach ($data as $key => $value) {
            //略过表头
            if ($key) {
                foreach ($value as $k => $item) {
                    //对于数字0以字符串形式输出
                    if ($item === 0) {
                        $data[$key][$k] = '0';
                    }
                }
            }
        }
        return collect($data);
    }


//    /**数据映射
//     * @param mixed $invoice
//     * @return array
//     */
//    public function map($invoice)
//    {
//        return [
//            $invoice->username,
//            $invoice->create_time,
//            //对于数字0以字符串形式输出或者使用WithStrictNullComparison接口
//            "$invoice->status",
//        ];
//    }
//
//
//    /**定义表单头
//     * @return array
//     */
//    public function headings()
//    {
//        return [
//            '名字',
//            '创建时间',
//            '状态',
//        ];
//    }


}
