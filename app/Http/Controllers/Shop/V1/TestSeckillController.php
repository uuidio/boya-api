<?php
/**
 * @Filename        TestSeckillController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use ShopEM\Http\Controllers\Shop\BaseController;
use GuzzleHttp\Client;
use ShopEM\Models\SecKillGood;
use ShopEM\Models\UserAccount;

class TestSeckillController extends BaseController
{


    public $user_with_headers = ['Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjQxZjVjMzNjOTMwNTQ2NjNjMzgzYmU2MGVlMmY2ZmY3NDM0YzdmNDY2Y2EzNGJiNDMwM2JlYzc4MmU5OGU0ZGIwYWJjZTk0YzI2ZDY3MDhkIn0.eyJhdWQiOiIyIiwianRpIjoiNDFmNWMzM2M5MzA1NDY2M2MzODNiZTYwZWUyZjZmZjc0MzRjN2Y0NjZjYTM0YmI0MzAzYmVjNzgyZTk4ZTRkYjBhYmNlOTRjMjZkNjcwOGQiLCJpYXQiOjE1NzYxNDA0NTcsIm5iZiI6MTU3NjE0MDQ1NywiZXhwIjoxNTc4NzMyNDU2LCJzdWIiOiIxIiwic2NvcGVzIjpbIioiXX0.seJXYPQXzl9cTuTQT6Deyc-eD4GP5qqjXBUWVzVQQ4p_yYcqDGoWUFLJLiAXeNFLL-TemcoR6xCTvoGkZ4b03PxXatYbc9DcBR5LMN4v_2jDBW1YDPP-c1CidZpx6F0hwNHFxbE57x8ltajpYaSIwLVSZO54mVOirHQvkgJ60lWKWc1dmArwBQ-a-TwkojSURj7XoS7Uks9H1r1I-KHnYYqq5Yn51pI_gKQN7Hbd9O65v8TD8ebAE_saGCHYKpRZyk9BkA9jGTPCdlX5z6fVf66jWCS4AmfP3aGIINvPsq_b0erxRaZ-iqdR0mI7a_1Z9Lr36uT0fEBuGcOVP_AwWFLdoT-rDgrYUqGQq9SiIo-UGbgZplP8vJCgwzPmw0p_4PsgbJCVjJqG5AGrkC4dQpx3h_0CE1-yihHfbwCjvsJSg7l-qn_Dou_E6DRh2ZwDqVRHBwpmAZdAadgj1_pCCS2B1CmchE6pXMJ3qeiF6O26bQuEY9SKDT6zuJnhbEyqJuXHsW2pV5DQGH7xroiapf_xydiPmch0jN0zVvDY0IshHHKSETYfADyMvX-3Sl5LU5fF8Hg4jDeu7GMQctM-5DQA3o4bBw0UfBRCGqU3NIykogU2Pbb6aaQKRo1JIxl3I0-4Uxtji00PfcmjmWTGSrRYORWxj4BJGzTVSogufcM'];



    public $token=[
        0=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjA5NzE5ZDM1ZjY0OGU5Y2YxZThlZGU0N2EwNzgyODA4Y2FhMzRhMzRhMWJkZjM3Yzc2MzAxNjFjNjlkNGE1OTFjMjY2ZjczMDA4ZGU2ZTVkIn0.eyJhdWQiOiI0IiwianRpIjoiMDk3MTlkMzVmNjQ4ZTljZjFlOGVkZTQ3YTA3ODI4MDhjYWEzNGEzNGExYmRmMzdjNzYzMDE2MWM2OWQ0YTU5MWMyNjZmNzMwMDhkZTZlNWQiLCJpYXQiOjE1NzYyMzI4NTYsIm5iZiI6MTU3NjIzMjg1NiwiZXhwIjoxNTc4ODI0ODU2LCJzdWIiOiIxMSIsInNjb3BlcyI6WyIqIl19.YTsG0Shdz7GjAm2dBTjQDC9UM4WG68ncmJ41_TYInbA10gaQKZiWoFH7lZwkGFe0s5_eiHGBwvWDTu_GVIxBZ2SbS-dSjbEJk7c0n8H9csRqDr2UV1XV0QjecXdUdKjaQWqGthMYhhCbvSz_JJ83NxB9CPf2cXSGDLZ_-qxEMajbNg6l4rpLRX_7SqQHEV4m4urKELlZwshyvo2LlVYYszB7Z3W7O3QmDFDuG1Fl5Layyq5fKcMTGiTC_nxywJBu7B8wzz6xjEEJKgWZdmf7hXEhmypdCbttKPcgGq57NFmvhUvyffl3G8KCAHaFoddNlyathuy1mjz3blU3HOosFHpilJsQLQWee6Ggjf-XEuEap78VoVpjTctIlu99jNHWp7kkcJCEvpbR11plRq4JyMk_vYZ-Zyx8sVO5lhHkYUw71KpwF3x98pJYtnhYCU1BDwI3T3AvPLIl5bzjI8gj51bFZeTH1bVN7Lpwt6qRFvHPpuZ_jrvthwIfgoPdmxfMXWfINjthZ9ekyXHxr8k0GLMWBCSNfJx5bFfo_VqB6CLqUgWg-oJW9Slkqx6yteCneOHmbI5UB97taPZsuA5ztD04Ll7XWXf8NyMenSCAOnHPZlUHPJzq7OZ1duUrp8xMUk5-BahJqQKNiLqeqYJd224aqkR10rjhqyUoBS6Z11I',
        'addr_id'=>'9',
        ],
        1=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImY3ZjQzOTQ0YzdmNjVjZWE2MTE5YWYxMDU4Yjc2YTgwODBlYzdkMDI1ZTFiZjE4NDM1NGIxNzc3MTg0ZDcyZmMxZjhjOTQ0NjkyNzhlYWRiIn0.eyJhdWQiOiI0IiwianRpIjoiZjdmNDM5NDRjN2Y2NWNlYTYxMTlhZjEwNThiNzZhODA4MGVjN2QwMjVlMWJmMTg0MzU0YjE3NzcxODRkNzJmYzFmOGM5NDQ2OTI3OGVhZGIiLCJpYXQiOjE1NzYyMzI4NTcsIm5iZiI6MTU3NjIzMjg1NywiZXhwIjoxNTc4ODI0ODU3LCJzdWIiOiIxMiIsInNjb3BlcyI6WyIqIl19.twSQRKUVVKpS51M93m6WPm7l2GLYkp8DG4h-HA_t2xq98fvkeHCP8TEpKx8xcTxKDlgrQbfzv3Ae9xCHDrPPJ0a_YG9jnEq_XqHCXTkmgepQO6A0M4fdNW6ojmLfUhKvWh2yw4fWOR2MlOGsVpmWM9Do77UcSaTY_g94e-VZk49g02ezT9KwYjj7-KHN-0TI01CZLwE6Ztjn0iYrPcbgIN5jupHgiF9MXewOgxirwCHWkATLCQIj1vq95yTx-JeJajuEMz0wMvWAb9-cPVu13Qv1DjtR7Wr7XvGsQN3UshUilHXP7-gPwfj1Izk-QmcFC8oFvHbYbl3htjWqPWl35TYJCUoHFGxMPzkjKRPsD_FCmy0R9mASU8_NT5-LwLY2iFX8fPoCtbagc3kspstMQ264T6gsmUmudFfUlcJOxQh6YDro3raYYVVwDslaljcVr6nVIWX7Ky-1RuPXc_BligQV_UUwhfJLmAqCNF1cRVTBxZcnyyBlj8nCOkZxwiylwaaire4vtcM1pRqZ5wYEQKHt6wwHm2ISYZB7JdB5x6sPL_WGRXKscUBjD51ea1euJ8Q20VfBlITT6Qai5mVjzFTYixHtFgDmV9qz1sYxk42xVt7IJzJqsKpQ3kfxPieJS6kB5edB2lm0yBnup4H9dKGzGwwGmVWWSSdoQlvXJVU',
            'addr_id'=>'10',
        ],
        2=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImNlY2MwMDI0MTAyOGM4YzYxMjJiMTBmODhiZDJmMjk1ZGE3Njg1YzE2M2IwMDRhMjFiY2ViNmEzZGQ1YTIzNzk2YTdhYTRlZGU1N2JlYTY3In0.eyJhdWQiOiI0IiwianRpIjoiY2VjYzAwMjQxMDI4YzhjNjEyMmIxMGY4OGJkMmYyOTVkYTc2ODVjMTYzYjAwNGEyMWJjZWI2YTNkZDVhMjM3OTZhN2FhNGVkZTU3YmVhNjciLCJpYXQiOjE1NzYyMzI4NTcsIm5iZiI6MTU3NjIzMjg1NywiZXhwIjoxNTc4ODI0ODU3LCJzdWIiOiIxMyIsInNjb3BlcyI6WyIqIl19.GXaOyasstXFaCT6A_72_MzWraunXoTYOhJBySfCp7CDH6IP-IDcqUQuOHgj0sTc5xhg0Sx0lpcaKonUEQOFXIj9LGTlLEK3ESldmccFe4pwpW8hqhLSaP1FiZhuaIAaXgybdvju33JVd1FuDRrbINQdMpsHsKlrN_Wx2GeKJyhaAo-IWDchdQKx3Oc1OeX_9L6ktHKdJYHe2tdP0agyVH6AHjYiD1gzR1j7CDuQuAbngBQoYuYq_9kmWPkOivg3gQWAqGgA9nDN1w3viBaDH-NoPlDbXtmuhiBTr6lpSv8cwVlVrLUOn5nCupDThr0cEa3Nuk99SVfhSn0m-Hq5jO2KErtmUt6mTPdgrMbfUdx8Gp1TXvUFW5awlD0Xt6z_RXKy04FezaB9HAUaAbgGkGAGHo1YWVkt9cJnR7sITYl1KpDlK0yiEUa8CTW_hyZUqNN0adyEmbC2px0hFENDeoc89_lXIftm1-WX_Hb7mABXYtFJVAtbQQfY0bTgGlmPA3SZ_89B09uWpvly4VdZd1ZL49T_oCp0FsE68niyi6B-wWulYbFkGt9NjwL8SlCML2bCegxE_MX-Qqm71oxc-JOQlzHyPw5pqAZCG_4MkFoKkCFyaj8uhNU4o2rD2rNC6X92-UN-jbrUPS8H6Lu1lp-w42uCj982EUSD40FxGmPk',
            'addr_id'=>'11',
        ],
        3=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjE1NTg4NzVmZjBhMDg4M2ViMzQwNzg3MWYxMzBhMGE5YzNmYmM4Njg2YmI3Y2M0ZjMyMmI0NmM4NDdhZDA4OWY1MGY2YWMwYzc1NzJiMDhiIn0.eyJhdWQiOiI0IiwianRpIjoiMTU1ODg3NWZmMGEwODgzZWIzNDA3ODcxZjEzMGEwYTljM2ZiYzg2ODZiYjdjYzRmMzIyYjQ2Yzg0N2FkMDg5ZjUwZjZhYzBjNzU3MmIwOGIiLCJpYXQiOjE1NzYyMzI4NTcsIm5iZiI6MTU3NjIzMjg1NywiZXhwIjoxNTc4ODI0ODU3LCJzdWIiOiIxNCIsInNjb3BlcyI6WyIqIl19.QYusqQvyXnQByRNOoAX8Q2Qs5C-0s3u7IRWZLbTOg_lmxpLqSW8gGinCMXKDL8ivYd_C_PVWbSn9fLxqusg6V8yYyTbIxScNwuHr85gdinrfTUiZYZqLdMsGSdDHKnertf1E4d7FsIfM2l1UQhzWOJqjn7I2GNAHvTwmbOw7KW71pO4eOl1M86nkniUCRm4UmHslbNioQKK6CL5k687G0-LoHqIXV7iL6M9eEZ44_eKrhIdNtwlK1Ykp9DcVAnnz32VJ9HTQxJouJ2cnFp0URnL7P1Z6HfMBrakdkQaRe8ajthAPGwc_Zp_d0lI4Y0MkBQ3rPOWbsKxqAcM7PT6tpYKuIJV_HXrRs3FmoZcZxAIEzm8Ul3z1ry0vhgjctH0wH8rsCihSEYASIOzV8XYD_K7_T2UtGM09y203m54BMz3NL7KsrFTyewyzpkSUC_59Qsm28TYR368_k87nL7pB_WW8YpR4PjLxS4XtpBIGlhmDqbv-c-hEwvYZtikDvUQ_sw2s-uoWIBmcrqTWS--mpUviFhTq2eLymtEg0wsxfE8aT5wjyEeVD84w35ov2Q9pyE0hfJcRnRzxI0loA1HDi8IlwifhMCdUHBVMcAl5rdZjBj9Tpy0_cbH2T1UlsQGCmUWHQ25YRUmXFTCYQqh5OvR11MvtdeV4qEgKjaz0Y_Y',
            'addr_id'=>'12',
        ],
        4=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjZmMGM3MmVjMTg3MmMxMzJmYjVlOTAxZjViNjFlMTFiNWJmODBmOTBhNzc0NmNjMzllN2M2M2I2NDI1NmFmYmFhYWE3N2UwMDdkMjgwMzg2In0.eyJhdWQiOiI0IiwianRpIjoiNmYwYzcyZWMxODcyYzEzMmZiNWU5MDFmNWI2MWUxMWI1YmY4MGY5MGE3NzQ2Y2MzOWU3YzYzYjY0MjU2YWZiYWFhYTc3ZTAwN2QyODAzODYiLCJpYXQiOjE1NzYyMzI4NTgsIm5iZiI6MTU3NjIzMjg1OCwiZXhwIjoxNTc4ODI0ODU4LCJzdWIiOiIxNSIsInNjb3BlcyI6WyIqIl19.s_-De8KH6tpxwg_kd4SEj9zlugpYc_y28AME92EIZQ3vzBYQE3ODHGO4pKm0pDYS0-DyZduOgsJ7j5_WgvaeetX4hTai99AcIMyDGudLqAjzqkqA-zA0TYnenIZu81QJwpV-GiO7ZVFWz3iY3cYmYClO40tPhsZ0sVD6QecULVzF-qNw-F7F3_ostAiPcNMxpNHlXMCo3AnR2qe1xnmWdQa5koVSG2MaqZUfAOxOMkPdlqKBDy3Bab7T4zLXQD-bBhmev012L2d7F1PB9eB0gQfmtmL7UFwfYRQHL6MVX8lc_9QIrtZfyjqWm1G-NGLVPyuUgk5lHTbd74CE-4Kz3zm0nLcfw4yWGyvEeWJNBK5NZCN_Gzas7XDc9OVAxAfkxxRMibpth7j6UsFwvW-Zigqb5_5Bvx-AjbteZtoNRmJIAoVA9uD_a6kfzcmCF-zcDoxTpVIc7zkNdBMdn6isxDk69UXldu3Y2-0hG1actmigGhuSuUfGyGWPhxU1gzVixet7fV2q-RnpOQTbIYkq6T2ySGjn_ukF6dIchOYVGN-UVbX-dCPIEOZTKv5Bx6MsVNRILoBbZiCN8BnC0D1ZPTbrvQl_MgmmzkrOnEBRkou8LcBsHA5ma14_AWsm2B3xCYjQBEUpPlT7nad1cRXCqJa6b6Q40_0q5vEsdDu2mQ4',
            'addr_id'=>'42',
        ],
        5=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImNiNjIzMWQyYmNkZTIxNzAxY2UwM2QwNjQxNzE1MjdhNTljNzg5ZTE1M2MyYzEzMTVhMjc0YjE4MThjMjU0ZTJkYmEwZjlmOWVjYjhmNWQ2In0.eyJhdWQiOiI0IiwianRpIjoiY2I2MjMxZDJiY2RlMjE3MDFjZTAzZDA2NDE3MTUyN2E1OWM3ODllMTUzYzJjMTMxNWEyNzRiMTgxOGMyNTRlMmRiYTBmOWY5ZWNiOGY1ZDYiLCJpYXQiOjE1NzYyMzI4NTgsIm5iZiI6MTU3NjIzMjg1OCwiZXhwIjoxNTc4ODI0ODU4LCJzdWIiOiIxNiIsInNjb3BlcyI6WyIqIl19.IZ9EEVFku276XyM4elxD0JVcqWwszjWtHGaoe30v1Ha2I-R1_FLj96fqH1Qepu9ljmgF620OqOM-YtB47uOCxZGLsQXXqEUuxX8bhvDoxA4nzTzw_IA7OK9e_8wfkyynlY51HnvaKDs6CN5dvuDf8K59uZI_3YNaYp3Irt5IQlFMv_6AOmoi5lmGwEyGkCQTdnyyDokM3I16MwT4tq7xmEGHV6V_-iBZJylz68QaX1jxsRi9AnW5Nv5_YotPJCnzOeHPR5CixD3mZD235PqBdvb1Rm-CDxir8Dafs646kyofLUmxdZ-RgYsPowt4VrxUp0V9zmT7MXdNQRkG832w6qkrXOpybZ9-11p5fEdW8SCczaH10nCutudTBLSp5Kj7iH8LGcZjgh7UW-whPfGGWQ6L5BJJCBJEjqNpDrY1r13z3IvKvux8QI14qmQXgQP_uBnS2H3t1j0Py01XegrC9SXBuO4BZa_roW7w8SIDODmpoeqYa_oUssu1cpNYQbwdxvFaasS6qqg6pcFok861h0NA5f56I9q6CdRZDODU1V7xPx5GGrtqSmGYgOOeOxNmAl7nYkBX7NWRBjTsgLHmmp1ygjbKFyi3MB6NrVmGGjvGFYXAjE7RrKup01trHBT5ATKhJ2Mxa86ZV2RPXBEQndfo9i3PwiyhUu-BkBN1Z0Q',
            'addr_id'=>'43',
        ],
        6=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImFmZmI5NzllYWVjN2ZjMTYyNGE1NjMzODllNzFkMzY4NWQ0NTJiNzE0ZGViMjkzYzJlYjEwZTIwMTJlNGM3NjE2YjQ5ZDVmMDczZmE0MWJlIn0.eyJhdWQiOiI0IiwianRpIjoiYWZmYjk3OWVhZWM3ZmMxNjI0YTU2MzM4OWU3MWQzNjg1ZDQ1MmI3MTRkZWIyOTNjMmViMTBlMjAxMmU0Yzc2MTZiNDlkNWYwNzNmYTQxYmUiLCJpYXQiOjE1NzYyMzI4NTksIm5iZiI6MTU3NjIzMjg1OSwiZXhwIjoxNTc4ODI0ODU5LCJzdWIiOiIxOSIsInNjb3BlcyI6WyIqIl19.miv9BqDA7TaaxnHj-H8xamTytYjwYgoLBmNJDA-AY-U0PcDSqYj9NghqVpL5ziFG9bAxawNecLpM77dWTvVRlDQaRDkLcNuEI90Hrf4oCh-U9R7AQuvkx10hO6mFX2PHCG8gF5vko1XnuJcZldBvkNZZXJGhXrk4Rca7Ro0QYpcXEsjZwbyXdx5QV8z6_kiGJCKX-9Z3Db3Fhb2ZdzA_VN30P53NOfgsqCyul56uXkOHD0M9V0hDEx1W28kR0Gd1dyqXtdv5-QHxO6HFTB6msf2obKY7OsiFjNZCs1lSw2RvXsxenwlthHuqoFQugCfluZpvQwL5luBiD7j8mylDo31uNzABM7tf8jpQzc16vPtl19ABNDsF3rKHbiP9adb3_CO43p13Nqvh51XXmN_SlxXO3Kak62YqqwRHa0pQPWmI-PoKwWpvV97pf-xroXLknceV8rphmUMnNFaXA_25N3NUNG9IeCef3kIG5cARKtMrP5wxxIMqdm6n4H15VFNIOr-BmGeecBs9ToJStRqsnf_NB44i-agtR3DEA4eMBEAAIZURq4A9_E1hCU5EcWHSDJTFgN_8KCwFapm648Yg9TvJIDJcMK5Bnzzned9o6Wdu3HVoVcjBfkqBCTBqLJ0jYa9lY9q3WGX5uWuNJafTdJs_pOtGQ0cHar5jG4i44uQ',
            'addr_id'=>'28',
        ],
        7=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjU0MjE2YThlZjgxYTY3M2U4ZWU5Mzc5ZDQ5ZDgzNzI1YzQ5NDAzYWJiZmIwZTEyYmFkYTMzNDI2MmZhMDNjOWQxNWQ4ZmRjMzcxZjRhNjhmIn0.eyJhdWQiOiI0IiwianRpIjoiNTQyMTZhOGVmODFhNjczZThlZTkzNzlkNDlkODM3MjVjNDk0MDNhYmJmYjBlMTJiYWRhMzM0MjYyZmEwM2M5ZDE1ZDhmZGMzNzFmNGE2OGYiLCJpYXQiOjE1NzYyMzI4NTksIm5iZiI6MTU3NjIzMjg1OSwiZXhwIjoxNTc4ODI0ODU5LCJzdWIiOiIyMCIsInNjb3BlcyI6WyIqIl19.MYNSw5lNFBiav-nbttbqhCO4SlXLY2dnKxWVer_mOM0y8qHR-Y9HEmti2S60AcJDuIh8totdTUEgSo7TOaXbl9j2zynCBtvjze0c6I4C_9XHMI5FoIRkCauSI4cWpHXBeVspA46_GdUFdADQqa8kkAKWjckJOatVDtazsT5TQZv-0cx2sO4TjwiMJuuFDB1jgqG3xQjsMuoOrGP7Kx_ZWB0jBHOBTmNzhVeUyxBg8LSsAlh2uWy0w7F5MXXaPeKetf46hXPDCmL7gVzLeO3Qr5PwaRVCUijeOiCqXoK2RSpqC7WRaMuY_3pNGLDc_joWiCL7PYF7uNefEHyYHPjwfOHNdpVm_2H3M-rpDKpghVpXMZnAK5pQIDCfGgxdiuo9Y-R4MaAMWJZ5G7fP1_diu26kp8VO2qZhEw7maSOerX11GkHx8RgVnoGiV0Y9uCBd6SH4MzpR6zWlhoD47Bamy5I_F9W5RjF0K_86-wYLRD4EWL36HU3IK-gkh9jmwgBBJcvAMPO99eOswkRWWazCQZKBPBH201p9pEe9OByIiFlagj4J7QhUmtVeZRF9VCmp5SkJSGMjtjNPmLf5rUCqTIB9toUZjkZhcJNFGojcEJ_M1jEjuSA245CVpBiZw1YCbo7uI00Q9t42v5idaHF5UFY16BHCNN_pmtIkTioLEYw',
            'addr_id'=>'25',
        ],
        8=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImMxZDc0MWYzZGRiOWQzZmU0N2NkZWVjMzdlY2E0ZWQwYzA2N2U2MDU5NDhmYmFjYzgyNGY2NzA5Zjg3ZDllMDAyZjg2ZGU0MDg1ZjdkNmFlIn0.eyJhdWQiOiI0IiwianRpIjoiYzFkNzQxZjNkZGI5ZDNmZTQ3Y2RlZWMzN2VjYTRlZDBjMDY3ZTYwNTk0OGZiYWNjODI0ZjY3MDlmODdkOWUwMDJmODZkZTQwODVmN2Q2YWUiLCJpYXQiOjE1NzYyMzI4NjAsIm5iZiI6MTU3NjIzMjg2MCwiZXhwIjoxNTc4ODI0ODYwLCJzdWIiOiIyMSIsInNjb3BlcyI6WyIqIl19.okbf-JX5ZSlVj3eKxPnSZhPvHJS5SAKUhaHarhIKVIVOSUsKZkHqVq59Z14e9zuvpzNE6MKyHBJzL5oKr2OJJZhKsPYaf2N7dojv_ERs7qeqxqearj-i-MWGOa-bnkguLVx8ehlyXnm4VbgoR_EwfP6FZ9HNj6XQS7m_cCCL0yvBwxm9ktBfsvBDcQcOC7kkG4m0snoyg5a9a2dpgY5bleKT0zBQ6qpgqQnoHuRCj57RZsvpnMZBTOwu104Pk6dfOcPV-kQmKvi8_igrT9eTxsDdjErfC7WdKKPpJFYDDBL5ezhrXXm9gPcyGaKr3ke_vhj9GCiN6vPoWXksw0_r4TgUD4efGAfHPndeqZDUdhXM1XAd3gvOOeHJBME3CHvyM93lTi6G21JFIa9UFjtvWaC49mM4xjwvPxhBBMhqf7GT3RrJ8J7dH3rQxjBRn7XHnTeKc0bnc9g4-48VSOjmOGrDA3K22BuWluQlIQ-uKvu1Ce5l9b727sWcNl0LickVzdz0BwUIoLrYx0TDKkFyEH4LGL0hUFClbwN8CpqB88MwaEbSTm4NgVKxdALNoSbB_HPZ_wbZY-iT1GmUBTzjxJv7aEmhJbs7FbvGPBrg8neVrIIr207wITalZ9jzqC0VtFOQ_ptD0fYGOzkksxuMgcvnpESpQhQl3xEPbTMsEwA',
            'addr_id'=>'39',
        ],
        9=>['Authorization' =>'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjQ2NTMyOWQ3N2Q0NGRmODNjODExZjZiOTczNmQzZTcwNGZhNDBhZWVmMjNlNWYzMDIwZWU1NTcyZWJlZWUxNGIzYzNhODg4YzMzNWY5ODkzIn0.eyJhdWQiOiI0IiwianRpIjoiNDY1MzI5ZDc3ZDQ0ZGY4M2M4MTFmNmI5NzM2ZDNlNzA0ZmE0MGFlZWYyM2U1ZjMwMjBlZTU1NzJlYmVlZTE0YjNjM2E4ODhjMzM1Zjk4OTMiLCJpYXQiOjE1NzYyMzI4NjAsIm5iZiI6MTU3NjIzMjg2MCwiZXhwIjoxNTc4ODI0ODYwLCJzdWIiOiIyMiIsInNjb3BlcyI6WyIqIl19.Dw1YCyO_vDZIDf96sTPXarSx7PzCE-eqzeSYzHewDKrBHegPIO0kTLG7LIYuFFKVIzyZMPtjTh4xA43VVkKoQb2kSWrSSMufq3yoAHPnGDRieM8C8_l7oOt1aO7qnyhezT58tmawDFCox3Hw88uorZQB2Uj4elyaOgE8mQ4wqxlqX54xT8zk4W-6UzeE1-KJkvLKOMVfqk-z_idFIcSUz__IWKrhPC1mxAn2fJzwFyeqA0ZBKjPNR3yslioSzBZK0E_aELPsSpaOQd58smvizJq8I9jp4YGFN00P75ra3IMRwZcGAFVK0zmtARMHOE72WOKVrUJ4zaLfyB7qsG4hsCWc9rp3acBQZw7kL7vonwMj5bkOF9l1ej_X8un_zsHghdGpCdoZB8AJWFt5gszRnofwtJKWEi1ZUx2D1otT9Yn6Lmdhq0Bw93iVHZI95BBJQfx_pwr_QsgiNW_xSrLGNOyizsb3tvC93aQ9OAnzE1pFGs34xEEuMO5H70Gw7T4fTid5x8PS-oR7Rfp_zNKUKIf0hVyfWjl_rHGDI55Uxynqqs6TgMF9nyJp4GVyRECWH8-fRkPKlZ1UT09JmbPWv9qzX4fAjEebSXTLHtYB9wzMU88xGYDL2BamPR6WKMbHIBRfAD8IFAws43PGW5JEUVPQz1YV_QAE1Sq5iB_v54c',
            'addr_id'=>'16',
        ],
    ];




    public function testSeckill()
    {

        $client = new Client();

        $url = env('APP_URL') . '/shop/v1/secKill/secKillStore';
//        $url = 'https://egotest.ytholidayplaza.com/shop/v1/secKill/secKillStore';

        foreach($this->token as $key =>$value){

            $post_data = [
                'sku_id'      => '263',
                'quantity'    => '1',
                'activity_id' => '98',
            ];

            $respond = $client->request('POST', $url, [
                'headers'     => ['Authorization'=>$value['Authorization']],
                'form_params' => $post_data
            ]);

            $respon = json_decode($respond->getBody()->getContents(), true);

        }

    }




    public function testCreateTrade()
    {

        $client = new Client();

        $url = env('APP_URL') . '/shop/v1/trade/create';
//        $url = 'https://egotest.ytholidayplaza.com/shop/v1/secKill/secKillStore';

        for($i=1;$i<=20;$i++){
            $this->fastBuyStore($i);
            $post_data = [
                'pick_type'      => '1',
                'fastbuy'    => '1',
                'addr_id'    => '1',
                'user_id'    => $i,
            ];

            $respond = $client->request('POST', $url, [
                'headers'     => ['Authorization'=> 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImIyZDUwMDM4ZTU3NzNjYTIxMzQwYzNmYjY4OWUyYWUzNTkzYjczODlhYjc1ZmZiZDMxZmZkYjg4YmM0Y2QzYjE3MDQ2NzFkYTg3MjdhMzdlIn0.eyJhdWQiOiIyIiwianRpIjoiYjJkNTAwMzhlNTc3M2NhMjEzNDBjM2ZiNjg5ZTJhZTM1OTNiNzM4OWFiNzVmZmJkMzFmZmRiODhiYzRjZDNiMTcwNDY3MWRhODcyN2EzN2UiLCJpYXQiOjE1ODA3MTI4OTMsIm5iZiI6MTU4MDcxMjg5MywiZXhwIjoxNTgzMzA0ODkyLCJzdWIiOiIxIiwic2NvcGVzIjpbIioiXX0.xT1UZogOlaoPwI3XzdY6M_rfrDBK-il9G5LMwkJzXYjVmAohlncrkgXCcxtIdiom6ymvFAemCKto5BRatJyeEw48cgUpe2hy94qm6Fx2dNu5q3bnGkCTBKRy0b-ac2tJLOgNwG_G4LtCH1ApUH-p1P3BL3nrpE10lcGctKQAIjbFgxaM5OlEuAMxoX-vTexz4DdQP1XKhGeetyHHTvffGxcKWWiFpsfLxBk9S8o97R8jeE_jkZrNKo3KGFAZsVYsHo-Wek-tqOgwljGUwUEwFG8M726z_Cyw0Z0QlAS7JRGRVhy4wPNwt0CJ4_vP-FlsvhsXx2RFVE66ff5wusGshumo-gGdr019SVpR7rf56GulTWtYpYTWZ2q_k3R62qt2ZiMk8_Hwd_aVL9tVeCF3btLFmnxXkv5TN0m_AhsUQbeGN7zxwhJ0g8ejJLKxFfWzmoVnVBuJ9WRaJYPPT4UHJWAHFDU6EGKTdK4EFeouTTFvWBhD_MM_BU1UiKbkvdlwkmo71rnlvfFUl1SelVqC0JXMyIPR_ofDhGi19jzFL3gDf4eE5qqZLpKm_MP5-dyP9lSUurW1Eir8Q5tGL8xhIiia9D-zSNSWENxNh8liLnIqqKCleTIpRmgLoereuw32KZkbYkhCSfZePt5taNMv3q2ZJwzIT273kYGkCY2JuCM'],
                'form_params' => $post_data
            ]);

            $respon = json_decode($respond->getBody()->getContents(), true);

        }

    }




    /**
     *  立即购买储存数据
     * @Author hfh_wind
     * @param $params
     * @return bool
     */
    public function fastBuyStore($user_id)
    {
        $params= [
            "sku_id" => 1,
            "quantity" => mt_rand(1, 10),
            "user_id" => $user_id,
            "goods_id" => 1,
            "shop_id" => 2,
            "goods_name" => "测试2",
            "goods_info" =>  [
                "id" => 1,
                "goods_name" => "测试2",
                "goods_info" => "测试",
                "shop_id" => 2,
                "gc_id" => 3,
                "goods_serial" => "111111111111111111",
                "goods_stock" => 10,
                "goods_marketprice" => "160.00",
            ],
            "goods_image" => "测试测试测试测试",
            "transport_id" => 0,
            "goods_price" => "160.00",
            "sku_info" => "",
            "is_checked" => "1",
        ];
        $key = md5($params['user_id'] . 'cart_fastbuy');
        $params = json_encode($params);

        Redis::set($key, $params);
        return true;
    }



    /**
     * 抢购加入购物车
     * @Author hfh_wind
     */
    public function testSecKillWaiting()
    {

        $client = new Client();

        $url = env('APP_URL') . '/shop/v1/secKill/secKillWaiting';
        foreach($this->token as $key =>$value){

            $post_data = [
                'sku_id'   => '263',
                'activity_id' => '98',
            ];

            $respond = $client->request('GET', $url, [
                'headers'     => ['Authorization'=>$value['Authorization']],
                'query' => $post_data,
            ]);

            $respon=json_decode($respond->getBody()->getContents(), true);

        }

    }




    /**
     * 测试生成订单
     * @Author hfh_wind
     */
    public function testSeckillCreateTrade()
    {

        $client = new Client();

        $url = env('APP_URL') . '/shop/v1/trade/create';
        foreach($this->token as $key =>$value){

            $post_data = [
                'addr_id'   => $value['addr_id'],
                'pick_type' => '0',
                'fastbuy'   => '1',
            ];

            $respond = $client->request('POST', $url, [
                'headers'     => ['Authorization'=>$value['Authorization']],
                'form_params' => $post_data
            ]);

            $respon = json_decode($respond->getBody()->getContents(), true);

        }

    }

    /**
     * 展示秒杀活动缓存信息
     * @Author hfh_wind
     * @return int
     */
    public function ShowSeckill(Request $request)
    {
        $redis=new Redis();

        $id=$request->id;

        if(empty($id)){
        return  $this->resFailed(414,'输入活动id');
        }
        $seckilldata=SecKillGood::where(['seckill_ap_id'=>$id])->get();
        $data=[];

        if(count($seckilldata) >0){
            $seckilldata=$seckilldata->toArray();

            foreach($seckilldata as $key =>$value){

                $sku_id=$value['sku_id'];
                $seckill_ap_id=$value['seckill_ap_id'];
                $data[$key]['goods_name']=$value['goods_name'];
                $data[$key]['title']=$value['title'];
                $data[$key]['sku_id']=$sku_id;
                $data[$key]['seckill_ap_id']=$seckill_ap_id;

                //按照sku 存盘
                $user_queue_key = "seckill_" . $sku_id . "_user_" . $seckill_ap_id;//当前商品队列的用户情况
                $goods_number_key = "seckill_" . $sku_id . '_good_' . $seckill_ap_id;//当前商品的库存
                $goods_record = "seckill_" . $sku_id . '_good_record_' . $seckill_ap_id;//商品购买库存

                $data[$key]['已售']=$redis::get($goods_record);
                $data[$key]['会员数量']=$redis::hlen($user_queue_key);
                $data[$key]['会员信息']=$redis::HgetAll($user_queue_key);
                $data[$key]['秒杀库存']=$redis::get($goods_number_key);
            }
        }


        return $data;
    }




    /**
     * 测试生成订单
     * @Author hfh_wind
     */
    public function GetUserToken()
    {

        $client = new Client();

        $url = 'https://egotest.ytholidayplaza.com/shop/v1/passport/login';

        $user_data = UserAccount::where('id', '>', 10)->limit(10)->get();
        foreach ($user_data as $key => $val) {
            $u = explode('_', $val['login_account']);
            $password = 'Hyflsc@' . substr($u[0], 2);

            $post_data = [
                'username' => $val['mobile'],
                'password' => $password,
            ];

            $respond = $client->request('POST', $url, [
//                'headers'     => $this->admin_with_headers,
                'form_params' => $post_data
            ]);

            $respon = json_decode($respond->getBody()->getContents(), true);
//            dd($respon['result']['access_token']);
            testLog('Bearer '.$respon['result']['access_token']);
        }


//        dd($respon);
    }


}