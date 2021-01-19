<?php

/**
 * Common.php
 * 放一些公共的方法
 */
namespace ShopEM\Services\DownloadAct;

/**
 * 
 */
class Common
{
    protected $suffix  = 'xls';

    //设置文件格式
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        return $this;
    }
    
	//过滤微信特殊表情
    public function filterEmoji($str)
    {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }

    public function filterNickname($nickname)
    {
        //替换emoji的代码如：
        $value = json_encode($nickname);
        $value = preg_replace("/\\\u[ed][0-9a-f]{3}\\\u[ed][0-9a-f]{3}|\^|\=/","?",$value);//替换成*
        $value = json_decode($value);

        return $value;
    }
    
    public function startWith($str, $needle)
    {
        return strpos($str, $needle) === 0;
    }
}