<?php
/**
 * @Filename validate.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

/**
 * 验证用户名是否合法.
 *
 * @Author moocde <mo@mocode.cn>
 * @param string $username
 * @return bool
 */
function validateUsername(string $username): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_-]{4,16}$/', $username);
}

/**
 * 验证是否是中国验证码.
 *
 * @param string $number
 * @return bool
 */
function validateChinaPhoneNumber(string $number): bool
{
    return (bool) preg_match('/^(\+?0?86\-?)?1[3-9]\d{9}$/', $number);
}