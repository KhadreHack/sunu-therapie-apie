<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'statut',
        'telephone',
        'photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function psychologue()
    {
        return $this->hasOne(Psychologue::class);
    }

    public function etudiant()
    {
        return $this->hasOne(Etudiant::class);
    }

    public function consultationsEtudiant()
    {
        return $this->hasMany(Consultation::class, 'etudiant_id');
    }

    public function consultationsPsychologue()
    {
        return $this->hasMany(Consultation::class, 'psychologue_id');
    }

    // Scopes
    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopePsychologue($query)
    {
        return $query->where('role', 'psychologue');
    }

    public function scopeEtudiant($query)
    {
        return $query->where('role', 'etudiant');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    // Helpers
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPsychologue()
    {
        return $this->role === 'psychologue';
    }

    public function isEtudiant()
    {
        return $this->role === 'etudiant';
    }
}
