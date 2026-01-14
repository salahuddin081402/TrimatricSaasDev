<?php
/**
 * TMX-IPAR | config/interior_parameters.php
 * Client Requisition – Interior Parameters config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Image Storage Settings
    |--------------------------------------------------------------------------
    |
    | All final images must live on the "public" disk:
    | storage/app/public/{country}/{company-slug}/images/parameter/interior/{table_name}/{file-name.ext}
    |
    | Public URL:
    | /storage/{country}/{company-slug}/images/parameter/interior/{table_name}/{file-name.ext}
    |
    */

    // Laravel filesystem disk
    'image_disk' => 'public',

    // Base folder under the company/country segment
    'image_base_path' => 'images/parameter/interior',

    // Optional: table-level folder overrides (currently 1:1 with table name)
    'tables' => [
        'cr_project_types'      => 'cr_project_types',
        'cr_project_subtypes'   => 'cr_project_subtypes',
        'cr_spaces'             => 'cr_spaces',
        'cr_item_categories'    => 'cr_item_categories',
        'cr_item_subcategories' => 'cr_item_subcategories',
        'cr_products'           => 'cr_products',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Validation
    |--------------------------------------------------------------------------
    */

    // Maximum image size in KB (use in max: rule)
    'max_image_kb' => env('IPARAM_MAX_IMAGE_KB', 2048),

    // Allowed image extensions for mimes: rule
    'image_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
];
