<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Exception;
use RuntimeException;

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
        $client->setAccessType('offline');

        $this->service = new Google_Service_Sheets($client);
    }

    /**
     * Append a new row or update an existing row based on chatId.
     *
     * @param array  $values One-dimensional array, e.g. ['123456789', 'John Doe', '2025-01-10 12:34:00']
     * @param string $range  The sheet+range to search and update, e.g. 'Sheet1!A:Z'
     *
     * @return bool True if successful, false otherwise
     */
    public function appendOrUpdateRow(array $values, string $range): bool
    {
        try {
            $chatId = $values[0];
            $rowIndex = $this->getRowIndexByChatId($chatId, $range);

            if ($rowIndex !== null) {
                $rowIndex++;
                return $this->updateRow($values, "Locations!A{$rowIndex}");
            } else {
                return $this->appendRow($values, $range);
            }
        } catch (Exception $e) {
            throw new RuntimeException('Google Sheets Error: ' . $e->getMessage());
        }
    }

    /**
     * Append a new row to the specified sheet.
     *
     * @param array  $values One-dimensional array, e.g. ['123456789', 'John Doe', '2025-01-10 12:34:00']
     * @param string $range  The sheet+range to append to, e.g. 'Sheet1!A:Z'
     *
     * @return bool True if successful, false otherwise
     */
    public function appendRow(array $values, string $range): bool
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
     * Update a specific row with new values.
     *
     * @param array  $values The values to update the row with.
     * @param string $range  The exact range to update, e.g. 'Sheet1!A5'
     *
     * @return bool True if successful, false otherwise.
     */
    public function updateRow(array $values, string $range): bool
    {
        try {
            $body = new Google_Service_Sheets_ValueRange([
                'values' => [$values],
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED',
            ];

            $this->service->spreadsheets_values->update(
                $this->spreadsheetId,
                $range,
                $body,
                $params
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find the row index of a specific chatId.
     *
     * @param string $chatId The chatId to search for.
     * @param string $range  The sheet+range to search in, e.g. 'Sheet1!A:Z'
     *
     * @return int|null The row index (starting from 1), or null if not found.
     */
    public function getRowIndexByChatId(string $chatId, string $range): ?int
    {
        $values = $this->getSpreadsheetValues($range);

        if ($values) {
            foreach ($values as $index => $row) {
                if (isset($row[0]) && $row[0] == $chatId) {
                    return $index + 1;
                }
            }
        }

        return null;
    }

    /**
     * Get the spreadsheet values for the given range.
     *
     * @param string $range The range to retrieve values from.
     *
     * @return array|null The values, or null if the range is empty.
     */
    public function getSpreadsheetValues($range): ?array
    {
        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            return $response->getValues();
        } catch (Exception $e) {
            return null;
        }
    }
    public function getRowValuesByIndex(int $rowIndex, string $sheet): ?array
    {
        try {
            $range = "{$sheet}!A{$rowIndex}:Z";
            $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
            $values = $response->getValues();

            return $values ? $values[0] : null;
        } catch (Exception $e) {
            error_log('Google Sheets Error: ' . $e->getMessage());
            return null;
        }
    }
}
