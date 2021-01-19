<?php
/**
 * @Filename        UploadVideo.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services\Upload;

use Illuminate\Http\Request;
use ShopEM\Traits\ApiResponse;

class UploadApk
{
    use ApiResponse;

    protected $file;

    protected $request;

    protected $filesystem;

    /**
     * 允许视频上传后缀
     *
     * @var array
     */
    protected $allowed_video_extensions = ["apk"];

    /**
     * UploadImage constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->filesystem = config('filesystems.default');
        $this->setResponseType(false);
    }

    /**
     * 上传保存视频
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function save()
    {
        $this->file = $this->request->file('apk');

        if (empty($this->file)) {
            return $this->resFailed(603, '上传安装包文件为空');
        }

        if (empty($this->file)) {
            return $this->resFailed(603, '上传安装包文件为空');
        }
        if ($this->file->getSize() > 62914560) {
            return $this->resFailed(603, '安装包文件不能大于60MB');
        }

        $extension = strtolower($this->file->getClientOriginalExtension());
        if ($extension && !in_array($extension, $this->allowed_video_extensions)) {
            return $this->resFailed(603, '上传安装包格式错误');
        }

        $video_name = str_replace('.' . $extension, '', $this->file->getClientOriginalName());

        $video_tag = $this->request->video_tag ?: 'default';

        $folder_name = 'apk/' . $video_tag . '/' . date("Ym", time()) . '/' . date("d", time());
        $path = $this->file->storeAs($folder_name,$video_name.".apk");

        $video_info = [];
        if ($path) {
            $video_info['apk_url'] = $path;
            $video_info['apk_tag'] = $video_tag;
            $video_info['apk_name'] = $video_name;
            $video_info['filesystem'] = $this->filesystem;

            return $this->resSuccess($video_info);
        }

        return $this->resFailed(603, errorMsg(603));
    }


}
