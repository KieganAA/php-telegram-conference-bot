<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Exception;

class GoogleSheetService
{
    private Google_Service_Sheets $service;
    private string $spreadsheetId;

    /**
     * Constructor.
     *
     * @param string $credentialsPath Path to the Google service account JSON file.
     * @param string $spreadsheetId   ID of the target Google Spreadsheet.
     *
     * @throws Exception
     */
    public function __construct(string $credentialsPath, string $spreadsheetId)
    {
        if (!file_exists($credentialsPath)) {
            throw new Exception("Google Sheets credentials file not found: $credentialsPath");
        }

        $this->spreadsheetId = $spreadsheetId;

        // Create a new Google client
        $client = new Google_Client();
        $client->setApplicationName('Conference Demo Bot');
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
        $client->setAuthConfig($credentialsPath);
        $client->setAccessType('offline'); // for refresh tokens, etc.

        $this->service = new Google_Service_Sheets($client);
    }

    /**
     * Append a single row of data to the specified sheet/range.
     *
     * @param array  $values One-dimensional array, e.g. ['John Doe','john@example.com','2025-01-10 12:34:00']
     * @param string $range  The sheet+range to append to, e.g. 'Registrations!A1'
     *
     * @return bool True if successful, false otherwise
     */
    public function appendRow(array $values, string $range = 'Sheet1!A1'): bool
    {
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => [$values],
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED', // or 'RAW' if you want exact
            ];

            $this->service->spreadsheets_values->append(
                $this->spreadsheetId,
                $range,
                $body,
                $params
            );

            return true;
        } catch (Exception $e) {
            // Log or handle the error as needed
            return false;
        }
    }

    /**
     * Append multiple rows to the specified sheet/range.
     *
     * @param array  $rows 2D array, e.g. [
     *   ['John','john@example.com','2025-01-10'],
     *   ['Jane','jane@example.com','2025-01-11']
     * ]
     * @param string $range The sheet+range to append
     *
     * @return bool
     */
    public function appendRows(array $rows, string $range = 'Sheet1!A1'): bool
    {
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $rows,
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED',
            ];

            $this->service->spreadsheets_values->append(
                $this->spreadsheetId,
                $range,
                $body,
                $params
            );

            return true;
        } catch (Exception $e) {
            // Log or handle error
            return false;
        }
    }

    // Add more read/update methods as needed...
}
