<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'email',
        'phone',
        'address',
        'is_active',
        'short_name',
    ];


        // "Midrand Primary School" becomes "MPS". Set a short name under My School to override it.
    public function abbreviation(): string
    {
        if ($this->short_name) {
            return $this->short_name;
        }

        $skip     = ['the', 'of', 'and', '&'];
        $initials = collect(preg_split('/\s+/', trim((string) $this->name)))
            ->reject(fn ($w) => $w === '' || in_array(strtolower($w), $skip, true))
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->join('');

        return $initials !== '' ? $initials : (string) $this->name;
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}