<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Facades\Config;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Http\Controllers\ReportController;

class Generate extends NumberFormat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $tries = 3;
    public $timeout = 60;
    protected $data;
    const FORMAT_CURRENCY_REAL_SIMPLE = '"R$"_-#,##0.00';

    public function __construct($data)
    {
        $locale = 'pt_br';
        $validLocale = \PhpOffice\PhpSpreadsheet\Settings::setLocale($locale);
        //$this->db = DB::connection('irroba');
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         $this->generate($this->data);
    }

    function connection(){

        Config::set('database.connections.irroba', array(
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'irroba',
            'username'  => 'irroba',
            'password'  => 'abc123456*',
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
        ));
        return true;
    }

    public function Generate($data)
    {
        $this->connection();
        $this->db = DB::connection('irroba');

        $query = $data['query'];
        $fields = $data['fields'];
        $report_name = $data['report_name'];
        $store_id = $data['store_id'];
        $start = $data['start'];
        $jobs_qtd = $data['jobs_qtd'];
        $report_id = $data['report_id'];
        $job = $data['job'];

        if(!is_dir(storage_path("app/reports/$store_id/$report_id"))){
            mkdir(storage_path("app/reports/$store_id/$report_id"),0777,true);
        }

        $delimiter = 3000;
        $this->spreadsheet = new Spreadsheet();
        $name_file = 'report_' . $report_name . date('d-m-Y-H-i-s') . '_' . $job . '.xlsx';
        $path = storage_path("app/reports/$store_id/$report_id/$name_file");

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(25);

        $count = 0;
        $format_columns = [];
        $sql = $query;
        $sql .= " LIMIT " . $start . ", " . $delimiter;
        $results = $this->db->select($this->db->raw($sql));

        foreach($fields as $fd){
            $count++;
            $sheet->setCellValueByColumnAndRow($count, 1, $fd['field']);
            if($fd['format']){
                $format_columns[] = $count;
            }
        }

        $row = 1;
            \Log::info(memory_get_usage() . ' - Job: ' . $job .' -antes_result');
            foreach($results as $result){
                $row++;
                $column = 1;
                foreach($result as $r){
                    $sheet->setCellValueByColumnAndRow($column, $row, $r);
                    // if(in_array($column, $format_columns)){
                    //     $sheet->getCellByColumnAndRow($column, $row)->getStyle()->getNumberFormat()->setFormatCode("'"R$_-"#,##0.00'");
                    // }
                    $column++;
                }
            }
            \Log::info(ini_get('memory_limit') . " -Job: ". $job .  ' -memory_limit');
            \Log::info(memory_get_usage() . ' - Job: ' . $job .' -pos_result');
            unset($results);
            \Log::info(memory_get_usage(). ' - job: '. $job .' -antes_save');
            $writer = new Xlsx($this->spreadsheet);
            $writer->setOffice2003Compatibility(true);
            $writer->save($path);
            \Log::info(memory_get_usage() . ' - job: ' . $job .' -pos_save');
            unset($this->spreadsheet);
            unset($sheet);
            unset($writer);

            if($jobs_qtd == 1){
                $this->doZip($store_id, $report_id, $report_name);
            }else{
                $this->sendNextReport($data);
                return true;
            }
    }

    public function sendNextReport($data = [])
    {
        $delimiter = 3000;
        $send['query'] = $data['query'];
        $send['fields'] = $data['fields'];
        $send['rows_qtd'] = $data['rows_qtd'] - $delimiter;
        $send['report_name'] = $data['report_name'];
        $send['store_id'] = $data['store_id'];
        $send['start'] = $data['start'] + $delimiter;
        $send['jobs_qtd'] = $data['jobs_qtd'] - 1;
        $send['report_id'] = $data['report_id'];
        $send['job'] = $data['job'] + 1;
        $this->dispatch($send)->onQueue('high')->delay(5);

    }

    public function doZip($store_id, $report_id, $report_name)
    {
        $zip = new ZipArchive;
        $path = tempnam(storage_path("app/reports/$store_id/$report_id"),$report_name);
        if ($zip->open($path, ZipArchive::OVERWRITE) === TRUE){
            if(is_dir(storage_path("app/reports/$store_id/$report_id")) && is_array(scandir(storage_path("app/reports/$store_id/$report_id")))){
                foreach(scandir(storage_path("app/reports/$store_id/$report_id")) as $files){
                    $file = storage_path("app/reports/$store_id/$report_id/$files");

                    if(is_file($file) && substr($file,-5) == ".xlsx"){
                        $to_del[] = $file;
                        $zip->addFile($file, $files);
                    }
                }
            }
            $zip->close();
        }
        $this->deleteArchives($to_del);
    }

    public function doZip2($store_id, $report_id, $report_name)
    {
        $zip = new ZipArchive;
        $path = tempnam(storage_path("app/reports/$store_id/$report_id"),$report_name);

        if ($zip->open($path, ZipArchive::OVERWRITE) === TRUE){
            \Log::info('passou zip open');
            if ($handle = opendir(storage_path("app/reports/$store_id/$report_id")))
            {
                \Log::info('passou handle');
                // Add all files inside the directory
                while (false !== ($entry = readdir($handle)))
                {
                    \Log::info('passou while');
                    if ($entry != "." && $entry != ".." && !is_dir(storage_path("app/reports/$store_id/$report_id").'/' . $entry) && substr($entry,-5) == ".xlsx")
                    {

                        $zip->addFile(storage_path("app/reports/$store_id/$report_id") . '/' . $entry);
                    }
                }
                closedir($handle);
            }

            $zip->close();
        }
    }

    public function deleteArchives($archives = [])
    {
        foreach($archives as $to_del){
            unlink($to_del);
        }

    }

}
