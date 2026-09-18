<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property int $user_id
 * @property int $shop_id
 * @property User|null $user
 * @property Shop|null $shop
 * @property Message|null $latest_message
 * @property string|int $unseen
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Conversation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['latest_message', 'unseen'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function getLatestMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }

    public function getUnseenAttribute()
    {
        if (! Auth::check()) {
            return '0';
        }

        $userId = Auth::id();
        $shopIds = Auth::user()->shops->pluck('id')->toArray();

        $participant = $this->participants()
            ->where(function ($q) use ($userId, $shopIds) {
                $q->where('user_id', $userId)->where('type', 'user');
                $q->orWhereIn('shop_id', $shopIds)->where('type', 'shop');
            })
            ->whereNull('last_read')
            ->first();

        return $participant ? 1 : 0;
    }
}
