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
                    'company_role' => $contact->company_role,
                    'secondary_email' => $contact->secondary_email,
                    'notes'       => $contact->notes,
                    'owner'       => optional($contact->user)->name,
                    'list'        => optional($source->contactList)->name,
                    'priority'    => optional($source->contactList)->priority,
                    'source'      => $source->name,
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
            'Company Role',
            'Secondary Email',
            'Notes',
            'Owner',
            'List',
            'Priority', 
            'Source',
            'Created At',
            'Updated At',
        ];
    }
}