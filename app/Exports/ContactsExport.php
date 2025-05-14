<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContactsExport implements FromCollection, WithHeadings
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        $rows = [];

        foreach ($this->records as $contact) {
            // Eager load sources and their contactList if not already loaded
            $contact->loadMissing('sources.contactList');
            foreach ($contact->sources as $source) {
                $rows[] = [
                    'first_name'  => $contact->first_name,
                    'last_name'   => $contact->last_name,
                    'email'       => $contact->email,
                    'phone'       => $contact->phone,
                    'owner'       => optional($contact->user)->name,
                    'list'        => optional($source->contactList)->name,
                    'priority'    => optional($source->contactList)->priority,
                    'source'      => $source->name,
                    'created_at'  => $contact->created_at,
                    'updated_at'  => $contact->updated_at,
                ];
            }
            // If a contact has no sources, you can optionally add a row with nulls for list/source
            if ($contact->sources->isEmpty()) {
                $rows[] = [
                    'first_name'  => $contact->first_name,
                    'last_name'   => $contact->last_name,
                    'email'       => $contact->email,
                    'phone'       => $contact->phone,
                    'owner'       => optional($contact->user)->name,
                    'list'        => null,
                    'priority'    => null,
                    'source'      => null,
                    'created_at'  => $contact->created_at,
                    'updated_at'  => $contact->updated_at,
                ];
            }
        }

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Phone',
            'Owner',
            'List',
            'Priority', 
            'Source',
            'Created At',
            'Updated At',
        ];
    }
}