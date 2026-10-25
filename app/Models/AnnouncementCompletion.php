<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementCompletion extends Model
{
    protected $fillable = ['announcement_id', 'user_id'];
}