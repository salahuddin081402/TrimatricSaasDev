<?php

/**
 * Usage:
 *   php patch_interior_parameters.php resources/views/backend/interior/parameters/index.blade.php
 *
 * This script:
 *   1) Rewrites function updateFormAction() to:
 *      - Create  → POST to current index/store URL
 *      - Edit    → PUT  to /backend/interior/{company}/parameters/update/{entity}/{id}
 *   2) Rewrites the Clear Form handler so it:
 *      - Clears only active entity fields + errors
 *      - Resets id and forces Create mode
 *      - Works even after validation failure
 *
 * No other parts of the Blade are touched.
 */

if ($argc < 2) {
    echo "Usage: php {$argv[0]} path/to/index.blade.php\n";
    exit(1);
}

$file = $argv[1];

if (!is_file($file)) {
    echo "File not found: {$file}\n";
    exit(1);
}

$orig = file_get_contents($file);
if ($orig === false) {
    echo "Failed to read file: {$file}\n";
    exit(1);
}

// Normalize line endings to "\n" for regex reliability
$contents = str_replace("\r\n", "\n", $orig);

// Backup original (exactly as-is)
$backup = $file . '.bak_' . date('Ymd_His');
if (file_put_contents($backup, $orig) === false) {
    echo "Failed to write backup: {$backup}\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| 1) Patch function updateFormAction()
|--------------------------------------------------------------------------
|
| We find from "function updateFormAction()" up to the closing "    }"
| at 4-space indent (outer function closing), and replace that block.
|
*/

$pattern1 = '/function updateFormAction\(\)\s*\{[\s\S]*?^    \}\n/m';

$replacement1 = <<<'JS1'
    function updateFormAction() {
        if (!ipForm || !formMethod) return;

        var entity = currentEntity || 'project_type';
        var mode   = modeBadge ? (modeBadge.dataset.mode || 'create') : 'create';
        var idVal  = recordId ? (recordId.value || '') : '';

        // Keep current entity synced to hidden input for backend
        if (entityInput) {
            entityInput.value = entity;
        }

        if (!baseUrl) {
            return;
        }

        // Trim trailing slashes to avoid double slashes
        var cleanBase = baseUrl.replace(/\/+$/, '');

        if (mode === 'edit' && idVal) {
            // Edit → hit dedicated update route:
            // backend/interior/{company}/parameters/update/{entity}/{id}
            ipForm.action = cleanBase + '/update/' +
                encodeURIComponent(entity) + '/' +
                encodeURIComponent(idVal);

            // Laravel sees this as PUT via _method spoofing
            formMethod.value = 'PUT';
        } else {
            // Create → POST back to index/store endpoint
            ipForm.action = cleanBase;
            formMethod.value = 'POST';
        }
    }

JS1;

$count1 = 0;
$contents = preg_replace($pattern1, $replacement1, $contents, -1, $count1);

/*
|--------------------------------------------------------------------------
| 2) Patch Clear Form handler
|--------------------------------------------------------------------------
|
| We find from:
|   "var btnClearForm = document.getElementById('btnClearForm');"
| down to the closing "    }" of that small block,
| and replace it with a smarter version.
|
*/

$pattern2 = '/^    var btnClearForm = document.getElementById\(\'btnClearForm\'\);\n[\s\S]*?^    \}\n/m';

$replacement2 = <<<'JS2'
    var btnClearForm = document.getElementById('btnClearForm');
    if (btnClearForm && ipForm) {
        btnClearForm.addEventListener('click', function (e) {
            // Stop native form.reset so we fully control what is cleared
            e.preventDefault();

            hideAlert();
            clearInvalidMarks(document);

            // Reset only currently active entity form section
            var activeSection = getActiveFormSection();
            if (activeSection) {
                activeSection.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (el.type === 'hidden') return;

                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = el.defaultChecked;
                    } else {
                        el.value = '';
                    }
                    el.classList.remove('ip-invalid');
                });
            }

            // Clear file inputs + localStorage previews
            clearFileInputs();
            clearAllStoredPreviews();

            // Clear id and force Create mode so submit = insert
            if (recordId) recordId.value = '';
            setMode('create', { keepId: false });
            updateFormAction();
        });
    }

JS2;

$count2 = 0;
$contents = preg_replace($pattern2, $replacement2, $contents, -1, $count2);

if (!$count1 && !$count2) {
    echo "No changes applied. Patterns may not match current Blade.\n";
    echo "Backup kept at: {$backup}\n";
    exit(0);
}

// Write updated Blade (with normalized "\n" line endings)
if (file_put_contents($file, $contents) === false) {
    echo "Failed to write updated file: {$file}\n";
    echo "Backup kept at: {$backup}\n";
    exit(1);
}

echo "Patched successfully.\n";
echo "Backup: {$backup}\n";
echo "Updated blocks: updateFormAction={$count1}, ClearForm={$count2}\n";
