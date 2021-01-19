<?php
/**
 * @Filename UploadImage.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services\Upload;

use ShopEM\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Image;
use Intervention\Image\Exception\ImageException;

class UploadImage
{
    use ApiResponse;

    protected $file;

    protected $request;

    protected $filesystem;

    /*
     * 允许上传后缀
     */
    protected $allowed_image_extensions = ["png", "jpg", "gif", 'jpeg'];
    protected $allowed_other_extensions = ['xls','csv','xlsx'];


    public function __construct($request)
    {
        $this->request = $request;
        $this->filesystem = config('filesystems.default');
        $this->setResponseType(false);
    }

    /**
     * 保存图片
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $this->file = $this->request->file('image');

        if (empty($this->file)) {
            return $this->resFailed(603, '上传图片文件为空');
        }

        $extension = strtolower($this->file->getClientOriginalExtension());
        if ($extension && !in_array($extension, $this->allowed_image_extensions)) {
            return $this->resFailed(603, '上传图片格式错误');
        }

        $pic_name = str_replace('.' . $extension, '', $this->file->getClientOriginalName());

        if ($this->filesystem === 'local' || $this->filesystem === 'oss') {
            $pic_tag = $this->request->pic_tag ?: 'default';

            $folder_name = 'images/' . $pic_tag . '/' . date("Ym", time()) . '/' . date("d", time());
            $path = $this->file->store($folder_name);
        }

        $image_info = [];
        if ($path) {
            $image_info['pic_url'] = $path;
            $image_info['pic_tag'] = $pic_tag;
            $image_info['pic_name'] = $pic_name;
            $image_info['filesystem'] = $this->filesystem;

            return $this->resSuccess($image_info);
        }

        return $this->resFailed(603, errorMsg(603));
    }

    public function createImage($filecontent)
    {
        $pic_tag = 'default';
        $folder_name = 'images/' . $pic_tag . '/' . date("Ym", time()) . '/' . date("d", time());

        $name = 'image_'.md5(time().getRandStr(4));

        $path = $folder_name.'/'.$name.'.jpeg';
        Storage::put($path, $filecontent);
        $image_url = Storage::url($name);
        if ($image_url) {
            $image_info['pic_url'] = $path;
            $image_info['pic_tag'] = 'default';
            $image_info['pic_name'] = $name;
            $image_info['filesystem'] = 'oss';

            return $this->resSuccess($image_info);
        }

        return $this->resFailed(603, errorMsg(603));
    }


    /**
     * 文件上传
     * author Huiho
     * @Date: 2020-05-25
     */
    public function uploadFile_document($type='')
    {

        $file  = $this->request->file('document');
        // 此时 $this->upload如果成功就返回文件名不成功返回false

        $fileName = $this->upload($file,'local',$type);

        if ($fileName){
            return  $fileName;
        }else{
            return false ;
        }

    }

    /**
    * 验证文件是否合法
    */
    public function upload($file, $disk='local_uploads',$type='') {
        // 1.是否上传成功
        if (! $file->isValid()) {
            return false;
        }

        // 2.是否符合文件类型 getClientOriginalExtension 获得文件后缀名
        $fileExtension = $file->getClientOriginalExtension();
        if(! in_array($fileExtension, $this->allowed_other_extensions)) {
            return false;
        }

        if($type && $fileExtension != $type){
            return false;
        }


        // 3.判断大小是否符合 2M
        $tmpFile = $file->getRealPath();
        if (filesize($tmpFile) >= 2048000) {
            return false;
        }

        // 4.是否是通过http请求表单提交的文件
        if (! is_uploaded_file($tmpFile)) {
            return false;
        }

        // 5.每天一个文件夹,分开存储, 生成一个随机文件名
        $fileName = date('Y_m_d').'/'.md5(time()) .mt_rand(0,9999).'.'. $fileExtension;

        if (Storage::disk('local')->put($fileName, file_get_contents($tmpFile)) ){
            if($type == 'xls' || $type == 'xlsx')
            {
                $filePath = 'uploads/'.$fileName;
            }
            elseif($type == 'csv')
            {
                $filePath = 'uploads/'.$fileName;
            }
            else
            {
                $filePath = env('APP_URL') . '/uploads/'.$fileName;
            }
            return  $filePath;
        }
    }



}