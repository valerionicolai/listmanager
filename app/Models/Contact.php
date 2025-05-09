<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Make sure this is imported

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'email',
        'phone',
        'company_role',
        'secondary_email',
        'notes',
        'user_id',
        // Do not include contact_list_id or source_id directly here if they are managed via pivot
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class, 'contact_source'); // Assumes pivot table is 'contact_source'
    }
}
