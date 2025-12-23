<?php

// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['chat_id', 'user_id', 'message'];

    // العلاقة مع المحادثة
    public function chat() {
        return $this->belongsTo(Chat::class);
    }

    // العلاقة مع المستخدم (مرسل الرسالة)
    public function user() {
        return $this->belongsTo(User::class);
    }
}
