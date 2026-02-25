<?php
/**
 * EspoCRM Call Flow Setup
 * Adds call disposition buttons to Lead and Task detail views
 *
 * Run inside Docker:
 *   docker cp call_flow_setup.php espocrm:/tmp/call_flow_setup.php
 *   docker exec espocrm php /tmp/call_flow_setup.php
 */

$base = '/var/www/html';

echo "=== EspoCRM Call Flow Button Setup ===\n\n";

// ── helpers ──────────────────────────────────────────────────────────────────

function ensureDir($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "  Created dir: $path\n";
    }
}

function writeJson($path, $data) {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "  Wrote: $path\n";
}

function writeFile($path, $content) {
    file_put_contents($path, $content);
    echo "  Wrote: $path\n";
}

function mergeJson($path, $newData) {
    $existing = [];
    if (file_exists($path)) {
        $existing = json_decode(file_get_contents($path), true) ?? [];
    }
    $merged = array_replace_recursive($existing, $newData);
    file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "  Merged: $path\n";
}

// ── directories ───────────────────────────────────────────────────────────────

echo "Step 1: Creating directories...\n";
$dirs = [
    "$base/custom/Espo/Custom/Resources/metadata/clientDefs",
    "$base/custom/Espo/Custom/Resources/metadata/entityDefs",
    "$base/custom/Espo/Custom/Resources/i18n/en_US",
    "$base/client/custom/src/views/lead",
    "$base/client/custom/src/views/task",
];
foreach ($dirs as $dir) ensureDir($dir);
echo "  OK\n\n";

// ── Step 2: Lead entityDefs — add callDisposition field ──────────────────────

echo "Step 2: Adding callDisposition field to Lead...\n";
mergeJson("$base/custom/Espo/Custom/Resources/metadata/entityDefs/Lead.json", [
    'fields' => [
        'callDisposition' => [
            'type'    => 'enum',
            'options' => [
                '', 'Reached', 'No Answer',
                'Left Voicemail', 'Callback Requested',
                'Not Interested', 'Qualified',
            ],
            'default'  => '',
            'required' => false,
        ],
    ],
]);
echo "  OK\n\n";

// ── Step 3: Lead clientDefs — register custom view + buttons ─────────────────

echo "Step 3: Configuring Lead action buttons...\n";
mergeJson("$base/custom/Espo/Custom/Resources/metadata/clientDefs/Lead.json", [
    'detailView' => 'custom:views/lead/detail',
    'menu'       => [
        'detail' => [
            'buttons' => [
                [
                    'label' => 'Reached',
                    'name'  => 'dispReached',
                    'style' => 'success',
                ],
                [
                    'label' => 'No Answer',
                    'name'  => 'dispNoAnswer',
                    'style' => 'default',
                ],
                [
                    'label' => 'Left Voicemail',
                    'name'  => 'dispVoicemail',
                    'style' => 'default',
                ],
                [
                    'label' => 'Callback Requested',
                    'name'  => 'dispCallback',
                    'style' => 'warning',
                ],
                [
                    'label' => 'Not Interested',
                    'name'  => 'dispNotInterested',
                    'style' => 'danger',
                ],
                [
                    'label' => 'Qualified ★',
                    'name'  => 'dispQualified',
                    'style' => 'primary',
                ],
            ],
        ],
    ],
]);
echo "  OK\n\n";

// ── Step 4: Task clientDefs — register custom view + buttons ─────────────────

echo "Step 4: Configuring Task action buttons...\n";
mergeJson("$base/custom/Espo/Custom/Resources/metadata/clientDefs/Task.json", [
    'detailView' => 'custom:views/task/detail',
    'menu'       => [
        'detail' => [
            'buttons' => [
                [
                    'label' => 'Complete Call',
                    'name'  => 'callCompleted',
                    'style' => 'success',
                ],
                [
                    'label' => 'No Answer',
                    'name'  => 'callNoAnswer',
                    'style' => 'default',
                ],
                [
                    'label' => 'Left Voicemail',
                    'name'  => 'callVoicemail',
                    'style' => 'default',
                ],
                [
                    'label' => 'Reschedule',
                    'name'  => 'callReschedule',
                    'style' => 'warning',
                ],
            ],
        ],
    ],
]);
echo "  OK\n\n";

// ── Step 5: i18n labels ───────────────────────────────────────────────────────

echo "Step 5: Writing translation labels...\n";
mergeJson("$base/custom/Espo/Custom/Resources/i18n/en_US/Lead.json", [
    'fields' => [
        'callDisposition' => 'Call Disposition',
    ],
    'options' => [
        'callDisposition' => [
            ''                   => '--',
            'Reached'            => 'Reached',
            'No Answer'          => 'No Answer',
            'Left Voicemail'     => 'Left Voicemail',
            'Callback Requested' => 'Callback Requested',
            'Not Interested'     => 'Not Interested',
            'Qualified'          => 'Qualified',
        ],
    ],
]);
echo "  OK\n\n";

// ── Step 6: Lead detail JS view ───────────────────────────────────────────────

echo "Step 6: Writing Lead detail view JS...\n";
$leadJs = <<<'JS'
/**
 * Custom Lead Detail View — Call Disposition Buttons
 * Extends the built-in Lead detail view to add call disposition actions.
 */
define('custom:views/lead/detail', ['views/lead/detail'], function (Dep) {

    return Dep.extend({

        // ── REACHED ─────────────────────────────────────────────────────────
        actionDispReached: function () {
            this._saveDisposition({
                callDisposition: 'Reached',
                status: 'In Process',
            }, 'Reached — status set to In Process');
        },

        // ── NO ANSWER ────────────────────────────────────────────────────────
        actionDispNoAnswer: function () {
            this._saveDisposition({
                callDisposition: 'No Answer',
            }, 'Marked: No Answer');
        },

        // ── LEFT VOICEMAIL ───────────────────────────────────────────────────
        actionDispVoicemail: function () {
            this._saveDisposition({
                callDisposition: 'Left Voicemail',
            }, 'Marked: Left Voicemail');
        },

        // ── CALLBACK REQUESTED ───────────────────────────────────────────────
        actionDispCallback: function () {
            this._saveDisposition({
                callDisposition: 'Callback Requested',
                status: 'In Process',
            }, 'Callback Requested');
        },

        // ── NOT INTERESTED ───────────────────────────────────────────────────
        actionDispNotInterested: function () {
            this.confirm({
                message: 'Mark this lead as Not Interested?',
                confirmText: 'Confirm',
            }, function () {
                this._saveDisposition({
                    callDisposition: 'Not Interested',
                    status: 'Dead',
                }, 'Marked: Not Interested');
            }, this);
        },

        // ── QUALIFIED ────────────────────────────────────────────────────────
        actionDispQualified: function () {
            this._saveDisposition({
                callDisposition: 'Qualified',
                status: 'Assigned',
            }, 'Qualified! Status set to Assigned');
        },

        // ── INTERNAL HELPER ──────────────────────────────────────────────────
        _saveDisposition: function (attrs, successMsg) {
            Espo.Ui.notify('Saving...');
            this.model.save(attrs, {
                patch: true,
                success: function () {
                    Espo.Ui.success(successMsg);
                },
                error: function () {
                    Espo.Ui.error('Save failed.');
                },
            });
        },

    });
});
JS;
writeFile("$base/client/custom/src/views/lead/detail.js", $leadJs);
echo "  OK\n\n";

// ── Step 7: Task detail JS view ───────────────────────────────────────────────

echo "Step 7: Writing Task detail view JS...\n";
$taskJs = <<<'JS'
/**
 * Custom Task Detail View — Call Action Buttons
 * Adds quick call-outcome buttons to Task records.
 */
define('custom:views/task/detail', ['views/task/detail'], function (Dep) {

    return Dep.extend({

        // ── COMPLETE CALL ────────────────────────────────────────────────────
        actionCallCompleted: function () {
            Espo.Ui.notify('Saving...');
            this.model.save({
                status: 'Completed',
            }, {
                patch: true,
                success: function () {
                    Espo.Ui.success('Call completed!');
                },
                error: function () {
                    Espo.Ui.error('Save failed.');
                },
            });
        },

        // ── NO ANSWER → DEFER ────────────────────────────────────────────────
        actionCallNoAnswer: function () {
            Espo.Ui.notify('Saving...');
            this.model.save({
                status: 'Deferred',
                description: (this.model.get('description') || '') +
                    '\n[No Answer — ' + (new Date().toLocaleDateString()) + ']',
            }, {
                patch: true,
                success: function () {
                    Espo.Ui.warning('No Answer — task deferred.');
                },
                error: function () {
                    Espo.Ui.error('Save failed.');
                },
            });
        },

        // ── LEFT VOICEMAIL ───────────────────────────────────────────────────
        actionCallVoicemail: function () {
            Espo.Ui.notify('Saving...');
            this.model.save({
                status: 'Completed',
                description: (this.model.get('description') || '') +
                    '\n[Left Voicemail — ' + (new Date().toLocaleDateString()) + ']',
            }, {
                patch: true,
                success: function () {
                    Espo.Ui.success('Voicemail logged — task completed.');
                },
                error: function () {
                    Espo.Ui.error('Save failed.');
                },
            });
        },

        // ── RESCHEDULE (open edit dialog) ────────────────────────────────────
        actionCallReschedule: function () {
            this.actionEdit();
        },

    });
});
JS;
writeFile("$base/client/custom/src/views/task/detail.js", $taskJs);
echo "  OK\n\n";

// ── Step 8: Clear EspoCRM cache ───────────────────────────────────────────────

echo "Step 8: Clearing EspoCRM cache...\n";
$cacheDirs = [
    "$base/data/cache",
    "$base/data/fast-cache",
];
foreach ($cacheDirs as $cacheDir) {
    if (is_dir($cacheDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        echo "  Cleared: $cacheDir\n";
    }
}
echo "  OK\n\n";

// ── Step 9: Fix permissions ───────────────────────────────────────────────────

echo "Step 9: Fixing file permissions...\n";
$paths = [
    "$base/custom",
    "$base/client/custom",
    "$base/data",
];
foreach ($paths as $p) {
    if (is_dir($p)) {
        exec("chmod -R 755 " . escapeshellarg($p));
        echo "  chmod 755: $p\n";
    }
}
echo "  OK\n\n";

// ── Done ──────────────────────────────────────────────────────────────────────

echo "=== Call Flow Setup Complete! ===\n";
echo "\n";
echo "What was installed:\n";
echo "  Lead buttons:  Reached | No Answer | Left Voicemail | Callback Requested | Not Interested | Qualified ★\n";
echo "  Task buttons:  Complete Call | No Answer | Left Voicemail | Reschedule\n";
echo "  New field:     Lead.callDisposition (enum)\n";
echo "\n";
echo "Next steps:\n";
echo "  1. Restart EspoCRM:  docker restart espocrm\n";
echo "  2. Hard-refresh your browser (Ctrl+Shift+R)\n";
echo "  3. Open any Lead or Task record — buttons appear in top-right menu\n";
echo "\n";
echo "Optional — add callDisposition to Lead layout:\n";
echo "  Admin > Entity Manager > Lead > Layouts > Detail View\n";
echo "  Drag 'callDisposition' field onto the layout and save.\n";
echo "\n";
