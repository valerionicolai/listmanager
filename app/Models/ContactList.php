<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough; // Import this

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'priority',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    /**
     * Get all contacts associated with this contact list through its sources.
     */
    public function contactsViaSources(): HasManyThrough
    {
        return $this->hasManyThrough(
            Contact::class, // The final model we want to access
            Source::class,  // The intermediate model
            'contact_list_id', // Foreign key on the Source model
            'id',              // Foreign key on the Contact model (related to pivot table's contact_id)
            'id',              // Local key on the ContactList model
            'contact_id'       // Local key on the Source model (related to pivot table's source_id)
        )->join('contact_source', 'contacts.id', '=', 'contact_source.contact_id')
         ->whereColumn('sources.id', 'contact_source.source_id'); // Ensure contact is linked via THIS source
    }
}
