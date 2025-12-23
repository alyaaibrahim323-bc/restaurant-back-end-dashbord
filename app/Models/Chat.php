<?php
// app/Models/Chat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['user_id', 'admin_id', 'is_closed'];

    // العلاقة مع المستخدم (العميل)
    public function user() {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع المسؤول
    public function admin() {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // العلاقة مع الرسائل
    public function messages() {
        return $this->hasMany(Message::class);
    }
}
