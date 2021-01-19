<?php
/**
 * @Filename        Live.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */
namespace ShopEM\Services\Live;

use ShopEM\Models\UserAccount;
use Illuminate\Support\Facades\Storage;
use ShopEM\Models\LivesLog;

class ForeshowService
{
    public function poster($param,$qrimg)
    {
        $config = array(
            'image'=>array(
                #array('url'=>$qrimg['img_url'], 'stream'=>0, 'left'=>456, 'top'=>-116, 'right'=>0, 'bottom'=>0, 'width'=>178, 'height'=>178, 'opacity'=>100),
                array(
                    'url'=>$param['img_url'],     //二维码资源
                    'stream'=>0,
                    'left'=>0,
                    'top'=>-0,
                    'right'=>0,
                    'bottom'=>0,
                    'width'=>800,
                    #'height'=>900,
                    'height'=>800,
                    'opacity'=>100
                ),
                #array('url'=>'yg.png', 'stream'=>0, 'left'=>0, 'top'=>-0, 'right'=>0, 'bottom'=>0, 'width'=>750, 'height'=>1334, 'opacity'=>30),
            ),
            'text'=>array(
                array(
                    'text'=>$param['title'],
                    'left'=>100,
                    'top'=>1100,
                    'fontPath'=>'SourceHanSerifCN-Bold-2.otf',     //字体文件
                    'fontSize'=>18,             //字号
                    'fontColor'=>'0,0,0',       //字体颜色
                    'angle'=>0,
                ),
                array(
                    'text'=>date("m-d H:i",time()),
                    'left'=>310,
                    'top'=>910,
                    'fontPath'=>'SourceHanSerifCN-Bold-2.otf',     //字体文件
                    'fontSize'=>20,             //字号
                    'fontColor'=>'255,255,255',       //字体颜色
                    'angle'=>0,
                )
            ),
            'background'=>'bg.png'          //背景图
        );
        #$filename = 'bg/'.time().'.jpg';
        return $this->createPoster($config,time());
    }


    /**
     * 生成宣传海报
     * @param array  参数,包括图片和文字
     * @param string  $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
     * @return [type] [description]
     */
    function createPoster($config=array(),$filename=""){
        //如果要看报什么错，可以先注释调这个header
        if(empty($filename)) header("content-type: image/png");
        $imageDefault = array(
            'left'=>0,
            'top'=>0,
            'right'=>0,
            'bottom'=>0,
            'width'=>100,
            'height'=>100,
            'opacity'=>100
        );
        $textDefault = array(
            'text'=>'',
            'left'=>0,
            'top'=>0,
            'fontSize'=>32,       //字号
            'fontColor'=>'0,0,0', //字体颜色
            'angle'=>0,
        );
        $background = $config['background'];//海报最底层得背景
        //背景方法
        $backgroundInfo = getimagesize($background);
        $backgroundFun = 'imagecreatefrom'.image_type_to_extension($backgroundInfo[2], false);
        $background = $backgroundFun($background);
        $backgroundWidth = imagesx($background);  //背景宽度
        $backgroundHeight = imagesy($background);  //背景高度
        $imageRes = imageCreatetruecolor($backgroundWidth,$backgroundHeight);
        $color = imagecolorallocate($imageRes, 0, 0, 0);
        imagefill($imageRes, 0, 0, $color);
        imageColorTransparent($imageRes, $color);  //颜色透明
        imagecopyresampled($imageRes,$background,0,0,0,0,imagesx($background),imagesy($background),imagesx($background),imagesy($background));
        //处理了图片
        if(!empty($config['image'])){
            foreach ($config['image'] as $key => $val) {
                $val = array_merge($imageDefault,$val);
                $info = getimagesize($val['url']);
                $function = 'imagecreatefrom'.image_type_to_extension($info[2], false);
                if($val['stream']){   //如果传的是字符串图像流
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = $function($val['url']);
                $resWidth = $info[0];
                $resHeight = $info[1];
                //建立画板 ，缩放图片至指定尺寸
                $canvas=imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);
                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'],$resWidth,$resHeight);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']) - $val['width']:$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']) - $val['height']:$val['top'];
                //放置图像
                imagecopymerge($imageRes,$canvas, $val['left'],$val['top'],$val['right'],$val['bottom'],$val['width'],$val['height'],$val['opacity']);//左，上，右，下，宽度，高度，透明度
            }
        }
        //处理文字
        if(!empty($config['text'])){
            foreach ($config['text'] as $key => $val) {
                $val = array_merge($textDefault,$val);
                list($R,$G,$B) = explode(',', $val['fontColor']);
                $fontColor = imagecolorallocate($imageRes, $R, $G, $B);
                $val['left'] = $val['left']<0?$backgroundWidth- abs($val['left']):$val['left'];
                $val['top'] = $val['top']<0?$backgroundHeight- abs($val['top']):$val['top'];
                imagettftext($imageRes,$val['fontSize'],$val['angle'],$val['left'],$val['top'],$fontColor,$val['fontPath'],$val['text']);
            }
        }

        //生成图片
        if(!empty($filename)){
            $fileName = 'images/default/' .md5(time()) .mt_rand(0,9999).'.png';
            $base64_image = "data:image/jpeg;base64," . base64_encode($imageRes);
            $res=Storage::disk('oss')->put($fileName, $base64_image);

            dd($res);
            imagedestroy($imageRes);
            if (!$res){
                return  false;
            }
            return  config('filesystems.disks.oss.domain') . $fileName;
            #imagepng($imageRes,'poster.png')
//            $res = imagejpeg ($imageRes,$filename,90); //保存到本地
//            imagedestroy($imageRes);
//            if(!$res) return false;
//            return $filename;
        }else{
            imagejpeg ($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }

    /**
     * 上场直播数据返回
     *
     * @Author linzhe
     */
    public function liveHistory($live_id)
    {
        $liveLog = LivesLog::where('live_id','=',$live_id)->where('delete','=','0')->select('*')->orderBy('id','desc')->first();
        $list['favorite'] = false;
        $list['foreshow'] = false;
        $list['playback'] = false;
        $list['like_count'] = $liveLog['like'] ? $liveLog['like'] : 0;
        $list['heat_count'] = $liveLog['heat'] ? $liveLog['heat'] : 0;
        $list['audience_count'] = $liveLog['audience'] ? $liveLog['audience'] : 0;
        $list['collect_count'] = $liveLog['collect'] ? $liveLog['collect'] : 0;
        $live_at = strtotime($liveLog['end_at']) - strtotime($liveLog['start_at']);
        $list['surface_img'] = $liveLog['surface_img'];
        $list['goodids'] = $liveLog['limit_goods'];

        if($liveLog['delete'] == '0'){
            $list['playback'] = true;
        }
        $remain = $live_at % 86400;
        $hours = intval($remain/3600);
        $remain = $remain % 3600;
        $mins = intval($remain/60);
        $secs = $remain % 60;
        if(strlen($hours) == 1){
            $hours = '0'.$hours;
        }
        if(strlen($mins) == 1){
            $mins = '0'.$mins;
        }
        if(strlen($secs) == 1){
            $secs = '0'.$secs;
        }
        $list['live_at'] = $hours.':'.$mins.':'.$secs;

        return $list;
    }
}
