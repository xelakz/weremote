<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockVendor;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Dropbox
{
    private $token;

    function __construct()
    {
        $this->token = env('DROPBOX_TOKEN', '');
    }

    public function report1()
    {
        try {
            $this->getStockProducts();
            $this->getAllVendorProducts();

            $headers  = ['SKU', 'stock_quantity', 'vendor'];
            $fileName = date('Y-m-d') . '-stocks-vendor.csv';
            $filePath = storage_path('app/' . $fileName);
            $file     = fopen($filePath, 'w+');
            fputcsv($file, $headers);

            $data = DB::table('stocks')
                ->join('stock_vendor', 'stocks.sku', 'stock_vendor.sku')
                ->join('vendors', 'vendors.vendor', 'stock_vendor.vendor')
                ->select('stocks.sku', 'stocks.qty', 'vendors.vendor')
                ->orderBy('stocks.sku', 'ASC')
                ->get()
                ->toArray();

            array_map(function ($d) use ($file) {
                fputcsv($file, [
                    $d->sku,
                    $d->qty,
                    $d->vendor
                ]);
            }, $data);
            fclose($file);
            $this->upload($fileName, $filePath);
        } catch (\Throwable $e) {
            echo "Something went wrong: " . $e->getLine() . ' - ' . $e->getMessage();
        }
    }

    public function report2()
    {
        $headers  = ['SKU', 'stock_quantity', 'vendor'];
        $fileName = date('Y-m-d') . '-stocks-vendor-withstocks.csv';
        $filePath = storage_path('app/' . $fileName);
        $file     = fopen($filePath, 'w+');
        fputcsv($file, $headers);

        $data = DB::table('stocks')
            ->join('stock_vendor', 'stocks.sku', 'stock_vendor.sku')
            ->join('vendors', 'vendors.vendor', 'stock_vendor.vendor')
            ->where('stocks.qty', '>', 0)
            ->select('stocks.sku', 'stocks.qty', 'vendors.vendor')
            ->orderBy('stocks.sku', 'ASC')
            ->get()
            ->toArray();

        array_map(function ($d) use ($file) {
            fputcsv($file, [
                $d->sku,
                $d->qty,
                $d->vendor
            ]);
        }, $data);
        fclose($file);
        $this->upload($fileName, $filePath);
    }

    public function report3()
    {
        $headers  = ['SKU', 'stock_quantity', 'vendor'];
        $fileName = date('Y-m-d') . '-stocks-vendor-withoutstocks.csv';
        $filePath = storage_path('app/' . $fileName);
        $file     = fopen($filePath, 'w+');
        fputcsv($file, $headers);

        $data = DB::table('stock_vendor')
            ->leftJoin('stocks', 'stocks.sku', 'stock_vendor.sku')
            ->join('vendors', 'vendors.vendor', 'stock_vendor.vendor')
            ->where('stocks.qty', '<=', 0)
            ->orWhereNull('stocks.qty')
            ->select('stock_vendor.sku', 'stocks.qty', 'vendors.vendor')
            ->orderBy('stocks.sku', 'ASC')
            ->get()
            ->toArray();

        array_map(function ($d) use ($file) {
            fputcsv($file, [
                $d->sku,
                $d->qty ?? 0,
                $d->vendor
            ]);
        }, $data);
        fclose($file);
        $this->upload($fileName, $filePath);
    }

    public function report4()
    {
        $this->getAllVendorProducts();

        $headers  = ['Vendor', 'total_stock_quantity'];
        $fileName = date('Y-m-d') . '-vendor-totalstocks.csv';
        $filePath = storage_path('app/' . $fileName);
        $file     = fopen($filePath, 'w+');
        fputcsv($file, $headers);

        $data = DB::table('stock_vendor')
            ->leftJoin('stocks', 'stocks.sku', 'stock_vendor.sku')
            ->select('stock_vendor.vendor', DB::raw('SUM(stocks.qty) as total_stocks_qty'))
            ->groupBy('stock_vendor.vendor')
            ->orderBy('stock_vendor.vendor', 'ASC')
            ->get()
            ->toArray();

        array_map(function ($d) use ($file) {
            fputcsv($file, [
                $d->vendor,
                $d->total_stocks_qty ?? 0
            ]);
        }, $data);
        fclose($file);
        $this->upload($fileName, $filePath);
    }

    private function upload($fileName, $filePath)
    {
        $params = [
            'path'            => '/Devtest/Reports/' . $fileName,
            'mode'            => "overwrite",
            'autorename'      => true,
            'mute'            => false,
            'strict_conflict' => false
        ];

        //The URL you're sending the request to.
        $url = env('DROPBOX_CONTENT_URL') . '/2/files/upload';

        //Create a cURL handle.
        $ch = curl_init($url);

        //Create an array of custom headers.
        $customHeaders = array(
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/octet-stream',
            'Dropbox-API-Arg: ' . json_encode($params)
        );

        //Use the CURLOPT_HTTPHEADER option to use our
        //custom headers.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

        //Set options to follow redirects and return output
        //as a string.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));

        //Execute the request.
        curl_exec($ch);
    }


    private function getAllVendorProducts()
    {
        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $this->token,
            'Dropbox-API-Arg' => json_encode([
                'path' => '/Devtest/Vendor/all-products-vendor.csv'
            ])
        ])->get(env('DROPBOX_CONTENT_URL') . '/2/files/download');

        if (!empty($response)) {
            $data = explode("\n", $response->body());
            foreach ($data as $key => $d) {
                if ($key == 0) {
                    continue;
                }

                $stockVendor = explode(',', $d);

                if (!empty($stockVendor[0])) {
                    Vendor::firstOrCreate([
                        'vendor' => trim($stockVendor[1]),
                    ]);

                    StockVendor::firstOrCreate([
                        'sku' => $stockVendor[0],
                    ], [
                        'vendor' => trim($stockVendor[1])
                    ]);
                }
            }
        }
    }

    private function getStockProducts()
    {
        $response = Http::withHeaders([
            'Authorization'   => 'Bearer ' . $this->token,
            'Dropbox-API-Arg' => json_encode([
                'path' => '/Devtest/Stocks/all-products-stock.csv'
            ])
        ])->get(env('DROPBOX_CONTENT_URL') . '/2/files/download');


        if (!empty($response)) {
            $data = explode("\n", $response->body());
            foreach ($data as $key => $d) {
                if ($key == 0) {
                    continue;
                }

                $stock = explode(',', $d);

                if (!empty($stock[0])) {
                    Stock::firstOrCreate([
                        'sku' => $stock[0],
                    ], [
                        'qty' => $stock[1]
                    ]);
                }
            }
        }
    }
}
