<?php

namespace Modules\HtmlGeneral\Models;

use Illuminate\Database\Eloquent\Model;

class HtmlBlockTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'content'];
}