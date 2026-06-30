<?php

namespace ChicpixiesBloomMailer\Subscribers;

use WP_Error;

if (!defined('ABSPATH')) exit;

/**
 * REST API endpoints for subscribers.
 *
 * Namespace: /cps/v1/bloom/subscribers
 *
 * GET    /                        - list with filters + pagination
 * DELETE /                        - bulk delete { ids: [] }
 * GET    /export                  - CSV download
 * POST   /import                  - CSV upload + field map
 * GET    /filters                 - distinct sources, platforms, lists
 */
class BloomRest
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        $routes = [
            'subscribers/import' => ['POST', 'import_subscribers'],
            'subscribers/segments' => ['POST', 'create_segment'],
        ];

        foreach ($routes as $route => $info) {
            $method = $info[0];
            $callback = $info[1];

            register_rest_route('cps/v1', '/bloom/' . $route, [
                'methods'  => $method,
                'callback' => [$this, $callback],
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // POST /bloom/subscribers/import
    //
    // Expects multipart/form-data:
    //   file  - the uploaded CSV file
    //   map   - JSON string: { "CSV Column": "db_field", ... }
    // -------------------------------------------------------------------------

    public function import_subscribers($request)
    {
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new WP_Error('import_error', 'No file uploaded.', ['status' => 400]);
        }

        $file = $files['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('import_error', 'File upload error.', ['status' => 400]);
        }

        // Validate mime / extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return new WP_Error('import_error', 'Only CSV files are supported.', ['status' => 400]);
        }

        // Decode the field map
        $map_raw = $request->get_param('map');
        if (empty($map_raw)) {
            return new WP_Error('import_error', 'Field map is required.', ['status' => 400]);
        }

        $map = json_decode($map_raw, true);
        if (!is_array($map)) {
            return new WP_Error('import_error', 'Invalid field map.', ['status' => 400]);
        }

        // Decode the field map
        $defaults_raw = $request->get_param('defaults');
        $defaults = json_decode($defaults_raw, true);
        if (!empty($defaults) && !is_array($defaults)) {
            return new WP_Error('import_error', 'Invalid defaults data.', ['status' => 400]);
        }

        // Email column must be mapped
        if (!in_array('email', array_values($map), true)) {
            return new WP_Error('import_error', 'You must map a column to Email.', ['status' => 400]);
        }

        // Parse CSV
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return new WP_Error('import_error', 'Could not read file.', ['status' => 500]);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (empty($headers)) {
            fclose($handle);
            return new WP_Error('import_error', 'CSV appears to be empty.', ['status' => 400]);
        }

        // Normalise headers (trim whitespace + BOM)
        $headers = array_map(function ($h) {
            return trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF");
        }, $headers);

        // Read all data rows
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            // Skip blank rows
            if (count(array_filter($row)) === 0) continue;

            // Combine with headers
            $combined = [];
            foreach ($headers as $i => $header) {
                $combined[$header] = $row[$i] ?? '';
            }
            $rows[] = $combined;
        }
        fclose($handle);

        if (empty($rows)) {
            return new WP_Error('import_error', 'No data rows found in the CSV.', ['status' => 400]);
        }

        $result = BloomBridge::import_csv($rows, $map, $defaults);

        return rest_ensure_response($result);
    }
}
