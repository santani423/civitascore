<?php

namespace Modules\Admission\Support;

/**
 * Field inti formulir (04 §D.2) — selalu ada; template hanya dapat
 * mengaturnya wajib/opsional/tersembunyi lewat `core_field_rules`
 * (FR-CFG-10), tidak dapat dihapus. Kunci field dinamis tidak boleh sama.
 */
final class CoreFormFields
{
    /** section => [field => default requirement (required|optional|conditional)] */
    public const FIELDS = [
        'identity' => [
            'full_name' => 'required', 'nationality' => 'required', 'nik' => 'conditional', 'passport_number' => 'conditional',
            'passport_country' => 'conditional', 'birth_place' => 'required', 'birth_date' => 'required', 'gender' => 'conditional',
            'address_line' => 'required', 'rt' => 'optional', 'rw' => 'optional', 'village' => 'optional', 'district' => 'optional',
            'city' => 'required', 'province' => 'required', 'postal_code' => 'optional', 'region_code' => 'optional',
            'special_needs' => 'optional', 'special_needs_note' => 'optional',
        ],
        'education' => [
            'primary_education_id' => 'required', 'report_scores' => 'conditional', 'nisn' => 'conditional',
        ],
        'choices' => [
            'choices' => 'required',
        ],
        'supporting' => [
            'guardians' => 'required', 'achievements' => 'conditional', 'info_source' => 'required', 'info_source_detail' => 'optional',
            'referral_code' => 'optional', 'kip_number' => 'conditional', 'kip_registration_number' => 'conditional', 'scholarship_interest' => 'optional',
            'scholarship_program_code' => 'optional',
        ],
    ];

    public const INFO_SOURCES = ['website', 'social_media', 'school_visit', 'expo', 'friend_family', 'alumni', 'maukuliah', 'search_engine', 'other'];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::FIELDS)));
    }

    /**
     * Kebutuhan efektif field inti setelah aturan template.
     *
     * @param  array<string, string>  $rules
     */
    public static function requirement(string $field, array $rules): string
    {
        foreach (self::FIELDS as $fields) {
            if (array_key_exists($field, $fields)) {
                return $rules[$field] ?? $fields[$field];
            }
        }

        return 'optional';
    }
}
