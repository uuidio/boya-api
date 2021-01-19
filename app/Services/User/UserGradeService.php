<?php
/**
 * @Filename        UserGradeService.php
 *
 */
namespace ShopEM\Services\User;

use ShopEM\Models\UserGrade;

class UserGradeService
{

    /**
     * @brief 保存会员等级
     *
     * @param $gradeData
     * @param $msg
     *
     * @return
     */
    public static function saveGrade($gradeData,&$msg)
    {
        self::_check($gradeData);

        $count = UserGrade::count();
        if($count <= 0 ) $gradeData['default_grade'] = 1;
        if(!isset($gradeData['grade_id']) || !$gradeData['grade_id'])
        {
            if($count == 8)
            {
                throw new \LogicException('会员等级个数最高为8个，现在已满上限');
            }
            $result = UserGrade::create($gradeData);
        } else {
            $gradeId = $gradeData['grade_id'];
            unset($gradeData['grade_id']);
            $result = UserGrade::where('id', $gradeId)->update($gradeData);
        }

        if(!$result)
        {
            throw new \LogicException('保存失败');
            return false;
        }
        return true;
    }

    /**
     * @brief 根据成长值获取会员当前的等级id
     *
     * @param $experience
     *
     * @return
     */
    public static function upgrade($experience)
    {
        $result = UserGrade::select('experience','id')->where('experience', '<=', $experience)->orderBy('experience', 'desc')->get();
        return $result[0]['id'];
    }

    /**
     * @brief 检测会员等级信息的合法性
     *
     * @param $postdata
     *
     * @return
     */
    private static function _check(&$postdata)
    {
        if($postdata['grade_name'] && !$postdata['grade_id'])
        {
            $count = UserGrade::count();
            if($count >8)
            {
                throw new \LogicException('等级总数不能超过8个');
                return false;
            }

            $list = UserGrade::select('id')->where('grade_name', $postdata['grade_name'])->first();
            if($list){
                throw new \LogicException('该等级名称已经存在');
                return false;
            }
        }

        if($postdata['default_grade'] == 1 && $postdata['experience'] !=0)
        {
            throw new \LogicException('该等级为默认等级，经验值必须为0');
            return false;
        }
        else
        {
            $isDefault = UserGrade::select('id', 'experience')->where('default_grade', 1)->first();
            if(!$isDefault)
            {
                $postdata['default_grade'] = 1;
                $postdata['experience'] = 0;
            }
        }
        return true;
    }



}