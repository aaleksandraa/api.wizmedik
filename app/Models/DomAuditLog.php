<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomAuditLog extends Model
{
    use HasFactory;

    protected $table = 'dom_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'dom_id',
        'user_id',
        'akcija',
        'stare_vrijednosti',
        'nove_vrijednosti',
        'ip_adresa',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'stare_vrijednosti' => 'array',
        'nove_vrijednosti' => 'array',
        'created_at' => 'datetime',
    ];

    const AKCIJE = [
        'create' => 'Kreiranje',
        'update' => 'Ažuriranje',
        'delete' => 'Brisanje',
        'verify' => 'Verifikacija',
        'activate' => 'Aktivacija',
        'deactivate' => 'Deaktivacija',
    ];

    /**
     * Relationships
     */
    public function dom()
    {
        return $this->belongsTo(Dom::class, 'dom_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopePoAkciji($query, $akcija)
    {
        return $query->where('akcija', $akcija);
    }

    public function scopePoKorisniku($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Accessors
     */
    public function getAkcijaLabelAttribute()
    {
        return self::AKCIJE[$this->akcija] ?? $this->akcija;
    }

    public function getUserNameAttribute()
    {
        return $this->user ? $this->user->name : 'Sistem';
    }

    /**
     * Helper methods
     */
    public function getChangedFields()
    {
        if (!$this->nove_vrijednosti || !$this->stare_vrijednosti) {
            return [];
        }

        $changes = [];
        foreach ($this->nove_vrijednosti as $field => $newValue) {
            $oldValue = $this->stare_vrijednosti[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public function hasAuditChanges()
    {
        return !empty($this->getChangedFields());
    }
}
