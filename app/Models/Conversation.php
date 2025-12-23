<?php
// app/Models/Conversation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'session_id',
        'user_id',
        'admin_id',
        'role',
        'message',
        'data',
        'is_closed'
    ];

    protected $casts = [
        'data' => 'array',
        'is_closed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع الأدمن
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // سكوب للجلسات النشطة
    public function scopeActive($query)
    {
        return $query->where('is_closed', false);
    }

    // سكوب للرسائل في جلسة محددة
    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    // سكوب لترتيب الرسائل
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}