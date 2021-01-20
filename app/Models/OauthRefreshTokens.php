<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class OauthRefreshTokens extends Model
{
    protected $table = 'oauth_refresh_tokens';
    protected $guarded = [];
}
