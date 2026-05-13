<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use App\Models\TenantSetting;
use App\Models\KitchenUnitConversion;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        $settings = TenantSetting::pluck('value', 'key');
        
        $spreadsheetId = $settings['google_spreadsheet_id'] ?? null;
        $jsonKey = $settings['google_service_account_json'] ?? null;
        $syncEnabledValue = $settings['google_sync_enabled'] ?? 'false';
        $enabled = ($syncEnabledValue === 'true' || $syncEnabledValue === '1' || $syncEnabledValue === 1);

        if (!$enabled || !$spreadsheetId || !$jsonKey) {
            return;
        }

        try {
            $this->client = new Client();
            $this->client->setAuthConfig(json_decode($jsonKey, true));
            $this->client->addScope(Sheets::SPREADSHEETS);
            $this->service = new Sheets($this->client);
        } catch (Exception $e) {
            Log::error("Google Sheets Initialization Error: " . $e->getMessage());
        }
    }

    public function isEnabled()
    {
        $settings = TenantSetting::pluck('value', 'key');
        $syncEnabledValue = $settings['google_sync_enabled'] ?? 'false';
        $enabled = ($syncEnabledValue === 'true' || $syncEnabledValue === '1' || $syncEnabledValue === 1);
        return $enabled && !empty($settings['google_spreadsheet_id']);
    }

    public function syncTransaction($sale)
    {
        if (!$this->isEnabled() || !$this->service) return;

        $settings = TenantSetting::pluck('value', 'key');
        $spreadsheetId = $settings['google_spreadsheet_id'] ?? null;
        if (!$spreadsheetId) return;
        $sheetName = 'Transaksi';
        
        $productNames = [];
        if ($sale->items) {
            foreach ($sale->items as $item) {
                $productNames[] = ($item->product->name ?? 'Unknown') . ' (' . $item->quantity . ')';
            }
        }
        $productsList = implode(', ', $productNames);

        $headers = ['ID Struk', 'Waktu', 'Kasir', 'Pelanggan', 'Produk Terjual', 'Total', 'Metode Bayar', 'Status'];
        $values = [[
            $sale->invoice_number,
            $sale->created_at->format('d/m/Y H:i'),
            $sale->cashier_name ?? ($sale->user->full_name ?? ($sale->user->username ?? 'System')),
            $sale->customer_name ?? '-',
            $productsList,
            (float) $sale->total_amount,
            $sale->payment_method ?? 'Cash',
            $sale->status
        ]];

        $this->appendRow($spreadsheetId, $sheetName, $headers, $values);
    }

    public function syncTransactions($sales)
    {
        if (!$this->isEnabled() || !$this->service || $sales->isEmpty()) return;

        $settings = TenantSetting::pluck('value', 'key');
        $spreadsheetId = $settings['google_spreadsheet_id'] ?? null;
        if (!$spreadsheetId) return;
        $sheetName = 'Transaksi';
        
        $headers = ['ID Struk', 'Waktu', 'Kasir', 'Pelanggan', 'Produk Terjual', 'Total', 'Metode Bayar', 'Status'];
        $values = [];
        
        foreach ($sales as $sale) {
            $productNames = [];
            if ($sale->items) {
                foreach ($sale->items as $item) {
                    $productNames[] = ($item->product->name ?? 'Unknown') . ' (' . $item->quantity . ')';
                }
            }
            $productsList = implode(', ', $productNames);

            $values[] = [
                $sale->invoice_number,
                $sale->created_at->format('d/m/Y H:i'),
                $sale->cashier_name ?? ($sale->user->full_name ?? ($sale->user->username ?? 'System')),
                $sale->customer_name ?? '-',
                $productsList,
                (float) $sale->total_amount,
                $sale->payment_method ?? 'Cash',
                $sale->status
            ];
        }

        $this->updateSheet($spreadsheetId, $sheetName, $headers, $values);
    }

    public function syncStock($data, $type = 'Warehouse')
    {
        if (!$this->isEnabled() || !$this->service) return;

        $spreadsheetId = TenantSetting::where('key', 'google_spreadsheet_id')->value('value');
        $sheetName = $type === 'Warehouse' ? 'Stok Gudang' : 'Stok Dapur';
        
        // Define default headers
        $headers = ['Nama Item', 'Stok Saat Ini', 'Satuan', 'Terakhir Update'];
        
        // Check if we should include conversion columns
        // (If at least one item has a meaningful conversion)
        $hasAnyConversion = false;
        foreach ($data as $item) {
            if ($type === 'Warehouse' && $item->unitConversions) {
                foreach ($item->unitConversions as $uc) {
                    if (strtolower($uc->from_unit) !== strtolower($uc->to_unit)) {
                        $hasAnyConversion = true;
                        break 2;
                    }
                }
            } elseif ($type === 'Kitchen' && $item->conversions) {
                foreach ($item->conversions as $kc) {
                    if (strtolower($kc->base_unit) !== strtolower($kc->convert_to_unit)) {
                        $hasAnyConversion = true;
                        break 2;
                    }
                }
            }
        }

        if ($hasAnyConversion) {
            // Re-order headers to put conversion after Satuan
            $headers = ['Nama Item', 'Stok Saat Ini', 'Satuan', 'Stok Konversi', 'Satuan Konversi', 'Terakhir Update'];
        }
        
        $values = [];
        foreach ($data as $item) {
            $row = [
                $item->name,
                (float) $item->stock,
                $item->unit ?? '-',
            ];

            if ($hasAnyConversion) {
                $convertedStock = '-';
                $convertedUnit = '-';

                if ($type === 'Warehouse' && $item->unitConversions) {
                    foreach ($item->unitConversions as $uc) {
                        if (strtolower($uc->from_unit) !== strtolower($uc->to_unit)) {
                            $convertedStock = round((float)$item->stock * (float)$uc->conversion_factor, 2);
                            $convertedUnit = $uc->to_unit;
                            break;
                        }
                    }
                } elseif ($type === 'Kitchen' && $item->conversions) {
                    foreach ($item->conversions as $kc) {
                        if (strtolower($kc->base_unit) !== strtolower($kc->convert_to_unit)) {
                            $convertedStock = round((float)$item->stock * (float)$kc->ratio, 2);
                            $convertedUnit = $kc->convert_to_unit;
                            break;
                        }
                    }
                }
                $row[] = $convertedStock;
                $row[] = $convertedUnit;
            }

            $row[] = now()->format('d/m/Y H:i');
            $values[] = $row;
        }

        $this->updateSheet($spreadsheetId, $sheetName, $headers, $values);
    }

    public function syncProducts($products)
    {
        if (!$this->isEnabled() || !$this->service) return;

        $spreadsheetId = TenantSetting::where('key', 'google_spreadsheet_id')->value('value');
        $sheetName = 'Produk';
        
        $headers = ['Nama Produk', 'Kategori', 'Harga Jual', 'HPP', 'Status'];
        $values = [];
        
        foreach ($products as $p) {
            $values[] = [
                $p->name,
                $p->category->name ?? '-',
                (float) $p->price,
                (float) $p->hpp,
                $p->is_available ? 'Aktif' : 'Non-aktif'
            ];
        }

        $this->updateSheet($spreadsheetId, $sheetName, $headers, $values);
    }

    public function syncRecipes($recipes)
    {
        if (!$this->isEnabled() || !$this->service) return;

        $spreadsheetId = TenantSetting::where('key', 'google_spreadsheet_id')->value('value');
        $sheetName = 'Resep';
        
        $headers = ['Produk', 'Bahan', 'Tipe', 'Jumlah', 'Satuan'];
        $values = [];
        
        foreach ($recipes as $r) {
            foreach ($r->items as $item) {
                $values[] = [
                    $r->product->name ?? '-',
                    $item->ingredient->name ?? ($item->ingredient->display_name ?? '-'),
                    $item->ingredient_type === 'kitchen' ? 'Dapur' : 'Gudang',
                    (float) $item->quantity,
                    $item->unit
                ];
            }
        }

        $this->updateSheet($spreadsheetId, $sheetName, $headers, $values);
    }

    public function syncInventory($transactions)
    {
        if (!$this->isEnabled() || !$this->service) return;

        $spreadsheetId = TenantSetting::where('key', 'google_spreadsheet_id')->value('value');
        $sheetName = 'Laporan Inventori';
        
        $headers = ['Tanggal', 'Lokasi', 'Nama Bahan', 'Tipe', 'Jumlah', 'Satuan', 'Catatan', 'Kasir'];
        $values = [];
        
        foreach ($transactions as $t) {
            $values[] = [
                is_string($t['date']) ? $t['date'] : $t['date']->format('d/m/Y H:i'),
                $t['location'],
                $t['item_name'],
                $t['type_label'],
                (float) $t['quantity'],
                $t['unit'],
                $t['notes'] ?? '-',
                $t['user_name'] ?? '-'
            ];
        }

        $this->updateSheet($spreadsheetId, $sheetName, $headers, $values);
    }

    protected function appendRow($spreadsheetId, $sheetName, $headers, $values)
    {
        try {
            $this->ensureSheetExists($spreadsheetId, $sheetName, $headers);
            
            $body = new ValueRange([
                'values' => $values
            ]);
            
            $params = ['valueInputOption' => 'RAW'];
            $this->service->spreadsheets_values->append($spreadsheetId, $sheetName . '!A1', $body, $params);
        } catch (Exception $e) {
            Log::error("GS Append Error: " . $e->getMessage());
        }
    }

    protected function updateSheet($spreadsheetId, $sheetName, $headers, $values)
    {
        try {
            $this->ensureSheetExists($spreadsheetId, $sheetName, $headers);
            
            // Clear existing data (except headers)
            $this->service->spreadsheets_values->clear($spreadsheetId, $sheetName . '!A2:Z', new \Google\Service\Sheets\ClearValuesRequest());
            
            $body = new ValueRange([
                'values' => $values
            ]);
            
            $params = ['valueInputOption' => 'RAW'];
            $this->service->spreadsheets_values->update($spreadsheetId, $sheetName . '!A2', $body, $params);
        } catch (Exception $e) {
            Log::error("GS Update Error: " . $e->getMessage());
        }
    }

    protected function ensureSheetExists($spreadsheetId, $sheetName, $headers)
    {
        try {
            $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
            $sheets = $spreadsheet->getSheets();
            $exists = false;
            
            foreach ($sheets as $s) {
                if ($s->getProperties()->getTitle() === $sheetName) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                // Create sheet
                $body = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                    'requests' => [
                        'addSheet' => [
                            'properties' => ['title' => $sheetName]
                        ]
                    ]
                ]);
                $this->service->spreadsheets->batchUpdate($spreadsheetId, $body);
            }

            // Check if headers exist (row A1 is empty)
            $response = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName . '!A1:A1');
            $values = $response->getValues();
            
            if (empty($values) || empty($values[0][0])) {
                // Add/Restore headers
                $headerBody = new ValueRange([
                    'values' => [$headers]
                ]);
                $this->service->spreadsheets_values->update($spreadsheetId, $sheetName . '!A1', $headerBody, ['valueInputOption' => 'RAW']);
            }
        } catch (Exception $e) {
            Log::error("GS EnsureSheet Error: " . $e->getMessage());
        }
    }
}
