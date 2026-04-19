<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        'date_of_birth',
        'gender',
        'is_admin',
        'is_active',
        'last_login_at',
        'preferences',
        'role',
        'bio',
        'avatar',
        'email_notifications',
        'order_notifications',
        'user_notifications',
        'system_notifications',
        'marketing_emails',
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
            'date_of_birth' => 'date',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'json',
            'email_notifications' => 'boolean',
            'order_notifications' => 'boolean',
            'user_notifications' => 'boolean',
            'system_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
        ];
    }

    /**
     * Send the password reset notification to the frontend URL.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get user's cart items
     */
    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get user's orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get user's reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get user's wishlist items
     */
    public function wishlistItems()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get user's addresses
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin()
    {
        return $this->is_admin || 
               (isset($this->role) && in_array(strtolower($this->role), ['admin', 'administrator', 'super_admin']));
    }

    /**
     * Get user's redirect path after login
     */
    public function getRedirectPath()
    {
        return $this->isAdmin() ? '/admin/dashboard' : '/dashboard';
    }

    /**
     * Get user's permissions
     */
    public function getPermissions()
    {
        $isAdmin = $this->isAdmin();
        
        return [
            'can_access_admin' => $isAdmin,
            'can_manage_users' => $isAdmin,
            'can_manage_products' => $isAdmin,
            'can_manage_orders' => $isAdmin,
            'can_manage_categories' => $isAdmin,
            'can_view_analytics' => $isAdmin,
        ];
    }
}
