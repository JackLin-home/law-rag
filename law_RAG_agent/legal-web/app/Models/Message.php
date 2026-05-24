<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // 重点：去掉 array 类型提示，直接定义属性。这是解决报错的唯一方法。
    protected $fillable = [
        'conversation_id',
        'role',
        'content'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
