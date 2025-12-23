<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Cart;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',

        ];
    }
    public function favorites()
{
    return $this->belongsToMany(Product::class, 'favorites', 'user_id', 'product_id')->withTimestamps();
}
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function routeNotificationForFcm()
    {
        return $this->deviceTokens->pluck('token')->toArray();
    }
    /////////////////////////
   public function reviews()
{
    return $this->hasMany(Review::class);
}



public function offers()
    {
        return $this->belongsToMany(Offer::class, 'user_offers')
                    ->withPivot('used_at', 'order_id', 'discount_amount')
                    ->withTimestamps();
    }


    public function hasUsedPromoCode($promoCode)
    {
        return $this->offers()
                    ->where('promo_code', $promoCode)
                    ->exists();
    }


    public function getTotalSavingsAttribute()
    {
        return $this->offers()->sum('user_offers.discount_amount');
    }

// ????????????????????????????
 public static function validateLogin($email, $password)
    {
        $user = static::where('email', $email)->first();

        if (!$user) {
            return [
                'success' => false,
                'field' => 'email',
                'message' => 'هذا البريد الإلكتروني غير مسجل في النظام'
            ];
        }

        if (!password_verify($password, $user->password)) {
            return [
                'success' => false,
                'field' => 'password',
                'message' => 'كلمة المرور غير صحيحة'
            ];
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }
}
