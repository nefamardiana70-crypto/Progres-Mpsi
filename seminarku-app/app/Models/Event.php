<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /** Nama penyelenggara, dipakai tampilan sebagai $ev['organizer_name']. */
    public function getOrganizerNameAttribute(): ?string
    {
        return $this->organizer?->name;
    }

    /**
     * Menambahkan penyelenggara + dua hitungan:
     *  registered = kursi terpakai (pendaftar yang tidak ditolak)
     *  pending    = menunggu keputusan panitia
     */
    public function scopeWithStats(Builder $q): Builder
    {
        return $q->with('organizer')->withCount([
            'registrations as registered' => fn ($r) => $r->where('status', '<>', 'ditolak'),
            'registrations as pending' => fn ($r) => $r->where('status', 'menunggu'),
        ]);
    }
}
