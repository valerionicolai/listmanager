<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;


class ContactsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithMapping
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return collect($this->records)->flatMap(function ($contact) {
            // Eager load sources and their contactList if not already loaded
            $contact->loadMissing('sources.contactList');
            
            return $contact->sources->map(function ($source) use ($contact) {
                return [
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
            });
        });
    }

    /**
     * Map the data to properly format dates for Excel
     */
    public function map($row): array
    {
        return [
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['company_role'],
            $row['secondary_email'],
            $row['notes'],
            $row['owner'],
            $row['list'],
            $row['priority'],
            $row['source'],
            // Convert dates to Excel format
            isset($row['created_at']) ? Date::dateTimeToExcel($row['created_at']) : null,
            isset($row['updated_at']) ? Date::dateTimeToExcel($row['updated_at']) : null,
        ];
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

    public function columnFormats(): array
    {
        // Columns are 0-indexed for PHP, but Excel uses A, B, C...
        // 'Created At' is the 12th column -> L
        // 'Updated At' is the 13th column -> M

        return [
            // For Italian date format (dd/mm/yyyy hh:mm:ss)
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY . ' hh:mm:ss',
            'M' => NumberFormat::FORMAT_DATE_DDMMYYYY . ' hh:mm:ss',
        ];
    }
}