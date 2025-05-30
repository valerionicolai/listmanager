<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Remove BelongsToMany if it's no longer used elsewhere in this file, or keep if needed for other relationships
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany; // Import HasMany
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description', // Add 'description' here
        'priority',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A ContactList has many Sources.
     */
    public function sources(): HasMany // Changed from BelongsToMany
    {
        // Assumes the 'sources' table has a 'contact_list_id' foreign key
        return $this->hasMany(Source::class);
    }

    public function contacts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Contact::class, // The final model we want to access
            Source::class,    // The intermediate model
            'contact_list_id', // Foreign key on sources table...
            'id',             // Foreign key on contacts table...
            'id',             // Local key on contact_lists table...
            'id'              // Local key on sources table... (assuming Contact is related to Source via contact_source pivot and Source has contact_id)
        )->join('contact_source', 'contacts.id', '=', 'contact_source.contact_id')
         ->where('contact_source.source_id', 'sources.id'); // This might need adjustment based on your exact pivot table structure for contacts to sources
    }

    // Remove the entire contactsViaSources method
    // public function contactsViaSources(): BelongsToMany
    // {
    //     // Assuming a Contact can belong to multiple Sources, and a Source can have multiple Contacts (many-to-many)
    //     // And a ContactList has many Sources. We want Contacts that are in any of the Sources of this ContactList.
    //     // This requires a more complex query or a different approach.
    //     // A simpler way if you have direct contact_list_contact pivot:
    //     // return $this->belongsToMany(Contact::class, 'contact_list_contact');
    // }
}
