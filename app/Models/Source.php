<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_list_id', // This foreign key is crucial for the BelongsTo relationship
        'user_id',
    ];

    /**
     * A Source belongs to one ContactList.
     */
    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_source');
    }

    // Remove the contactLists() BelongsToMany relationship if it's no longer needed
    // public function contactLists(): BelongsToMany
    // {
    //     return $this->belongsToMany(ContactList::class, 'contact_list_source');
    // }
}
