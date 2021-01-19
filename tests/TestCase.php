<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $platform_with_headers = ['Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjE1YmE0NWRhMGM1ZWFkNTA4NWQ0Y2M5MTI1MWE5OTA2OGNjNzg5NjRhODA5MjdmNzE5OWY4OTcwNzlmZmE5MjRiNDc5MTkxYWVlYzczMzMxIn0.eyJhdWQiOiIyIiwianRpIjoiMTViYTQ1ZGEwYzVlYWQ1MDg1ZDRjYzkxMjUxYTk5MDY4Y2M3ODk2NGE4MDkyN2Y3MTk5Zjg5NzA3OWZmYTkyNGI0NzkxOTFhZWVjNzMzMzEiLCJpYXQiOjE1NTYxNzU1NTQsIm5iZiI6MTU1NjE3NTU1NCwiZXhwIjoxNTg3Nzk3OTU0LCJzdWIiOiIxMDAwMDAiLCJzY29wZXMiOlsiKiJdfQ.aGn4ThDftmSyzFstVjz2ryAqcJYeawUsRENhvDVZ7rhtbrVrz3Y4g8nbDhdDybhtSw1GyorBtARaH67GT1sSpHxEc-1fKmr_-9t-y7oqmSaquQREMLRfO45NuNx4lZZEdlvIuIX1xboiqJVaiLtbYP_RhfieuMm2ysKzA62-aLNcUwUix_9LpKpF_x_QHuW-E1aY-3wfKiAH1TRdPcxxdSXf6IAMyESq1xpQ-4FvzGOrYv_iUVGqWlTpueNva2xLOVWn7GVZxIFLHbv--Kn9UGe-1Mkya7SderpMszPiFMb-xPnIXrwcIKpm10llozl_cXo3Lxpl7ja7iORnRDnd00jjE3KXK8xzDE9mQwICy5IzWNcda7nEIRZjvitAn0musmSIuhlp_5e8usI3z_6uKMCnvED_tUViJGe2jte94eGc0EyP_gwvNj7zXppEy7x7rpzScfVo_SeiRzWSR6wYNMmWF_w155hHZJR-XvW3b28pG5CgLcI25diMuohnl0KuOnR7RkVGm-quBWLUyRqGGAqkYc7ZqNOfEQzOLcvCRpGgb5Oi71-WZbb_QnAXVNW3apE6SSvSGgtjwv-Li-M65FNV3mSbe4BaZn_x_QLn8BfZdfwn6GjIVC5VQ40aSZqFyoToI7nRUuzu4mqrEkaGS5G4s-UBbWjb5mWYu418OYw'];
}
