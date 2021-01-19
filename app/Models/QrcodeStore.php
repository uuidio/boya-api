<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class QrcodeStore extends Model
{
    protected $guarded = [];

    /**
     * [saveQr 获取及保存二维码]
     * @param  [type] $content [description]
     * @param  string $type    [description]
     * @param  string $size    [大小]
     * @return [type]          [description]
     */
    public static function getQr($content,$type='default',$size=300,$logo=false)
    {
    	$md5key = md5($content);
        if ($logo) $md5key = md5($content).'_logo';

    	$find = self::where('md5key',$md5key)->value('qrcode_url');
        $qrName = 'qrcodes/'.$type.'/' . $md5key . '.png';
    	if (!empty($find)) {
            $exists = Storage::exists($qrName);
            if ($exists) {
                return Storage::url($qrName);
            }else{
                self::where('md5key',$md5key)->delete();
            }
    	}
    	$qrContent = QrCode::format('png')->margin(1)->size($size)->errorCorrection('H');
        if ($logo) 
        {
            $logo_file = public_path('logo.png');
            $qrContent = $qrContent->merge($logo_file,.2,true);
        }
        $qrContent = $qrContent->generate($content);
        Storage::put($qrName, $qrContent);
        $qrcode_url = Storage::url($qrName);

        $data = [
        	'md5key' => $md5key,
        	'content' => $content,
        	'qrcode_url' => $qrName,
        	'filesystem' => config('filesystems.default'),
        ];
    	self::create($data);

    	return $qrcode_url;
    }

    /**
     * [clearQr 定时清除二维码]
     * @return [type] [description]
     */
    public static function clearQr()
    {
        try {
            //14天前的清除
            $date = date('Y-m-d 00:00:00', strtotime('-7 day'));
            $data = self::where('created_at','<=',$date)->limit(100)->get()->toArray();
            foreach ($data as $key => $value) 
            {
                $qrcode_url = $value['qrcode_url'];
                $exists = Storage::exists($qrcode_url);
                if ($exists) 
                {
                    if (Storage::delete($qrcode_url)) {
                        self::where('id',$value['id'])->delete();
                    }
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }


    //获取二维码，不进行数据库保存
    public static function tempQr($content,$size=300,$logo=false)
    {
        $md5key = md5(microtime());
        if ($logo) $md5key = md5(microtime()).'_logo';

        $qrName = 'qrcodes/temp/'. date('Ymd').'/'. $md5key . '.png';
        $qrContent = QrCode::format('png')->margin(1)->size($size)->errorCorrection('H');
        if ($logo) 
        {
            $logo_file = public_path('logo.png');
            $qrContent = $qrContent->merge($logo_file,.2,true);
        }
        $qrContent = $qrContent->generate($content);
        Storage::put($qrName, $qrContent);
        $qrcode_url = Storage::url($qrName);

        return $qrcode_url;
    }

    //清除缓存目录下的二维码
    public static function clearTempQr()
    {
        $todayTemp = 'qrcodes/temp/'. date('Ymd');
        $tempDirs = Storage::directories('qrcodes/temp');
        foreach ($tempDirs as $key => $value) 
        {
            if ($value != $todayTemp) {
                Storage::deleteDirectory($value);
            }
        }
        return true;
    }
}
