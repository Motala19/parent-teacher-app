<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationClosure extends Model
{
    protected $fillable = ['user_id', 'contact_id', 'closed_at'];

    protected $casts = ['closed_at' => 'datetime'];
}