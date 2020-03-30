<?php

namespace App\Http\Controllers;

use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpZip\ZipFile;
use ZipArchive;
use App\Jobs\Generate;

use PhpOffice\PhpSpreadsheet\IOFactory;


class ReportController extends Controller
{

    public function __construct()
    {
        $this->dir_storage =  ($_SERVER['DOCUMENT_ROOT'] .'/storage/');
        $locale = 'pt_br';
        $validLocale = \PhpOffice\PhpSpreadsheet\Settings::setLocale($locale);
        $this->db = new DB();
    }
    public function ok ()
    {
        return $this->db::table('irr_order')->select('*')->paginate(20);

    }

    public function reportCustomer(){
        $customers_id = $this->getCustomers();

        foreach(json_decode($customers_id,true) as $customer){
            foreach($customer as $customer_id){
                $results [] = $this->db::table('irr_order')
                ->join('irr_customer','irr_customer.customer_id','=','irr_order.customer_id')
                ->where('irr_order.customer_id',$customer_id)
                ->select('irr_customer.email',
                         'irr_customer.cellphone',
                         'irr_customer.firstname',
                         'irr_order.shipping_city',
                         'irr_order.shipping_zone',
                         'irr_order.shipping_country',
                         'irr_order.shipping_postcode',
                         'irr_customer.sex',
                         $this->db::raw('DATE_FORMAT(irr_customer.birthday, "%d-%m-%Y") as birthday'),
                         $this->db::raw('ROUND(SUM(irr_order.total),2) as total'))
                         ->first();
            }
        }

        $this->spreadsheet = new Spreadsheet();
        $path = storage_path('app/reports') . '/report.xlsx' ;

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->getDefaultColumnDimension()->setWidth(25);
        $count = 0;

        foreach($this->fields() as $field){
            $count++;
            $sheet->setCellValueByColumnAndRow($count, 1, $field);
        }

        $row = 1;
        foreach($results as $result){
            $row++;
            $column = 1;
            foreach($result as $key => $value){
                if($key == "shipping_postcode"){
                    $cep = str_replace('\'','',substr($value,0,5) . '-' .substr($value,5,3));
                    $sheet->setCellValueByColumnAndRow($column, $row, $cep);
                    $column++;
                }else{
                    $sheet->setCellValueByColumnAndRow($column, $row, $value);
                    $column++;
                }

            }
        }

        $writer = new Xlsx($this->spreadsheet);
        $writer->setOffice2003Compatibility(true);
        $writer->save($path);
        $writer->setOffice2003Compatibility(true);
        $writer->save($path);
        return response()->json(['success' => 'Relatório gerado! ']);

    }

    // public function sergios(){
    //     $citys = $this->getCitys();

    //     foreach(json_decode($citys,true) as $city){
    //         $results[] = $this->db::table()
    //     }
    // }

    public function getCitys()
    {
        $results = $this->db::table('irr_order')
        ->where('order_status_id','<>', 0)
        ->where('return_id', 0)
        ->where('email','not like', '%irroba.com.br%')
        ->where('order_status_id',5)
        ->orWhere('order_status_id',28)
        ->orWhere('order_status_id',23)
        ->orWhere('order_status_id',27)
        ->orWhere('order_status_id',3)
        ->orWhere('order_status_id',20)
        ->where('payment_code', '<>', 'out_ecommerce')->select('shipping_city')->distinct()->get();
        return json_encode($results);

    }
    public function fields(){
        $fields = [];

        $fields = ['Email','Telefone','Nome completo','Cidade','Estado','País','Cep','Genêro','Data de nascimento','Valor vitalício'];
        return $fields;
    }

    public function getCustomers(){
        $result = $this->db::table('irr_order')
        ->where('order_status_id','<>', 0)
        ->where('return_id', 0)
        ->where('email','not like', '%irroba.com.br%')
        ->where('order_status_id',5)
        ->orWhere('order_status_id',28)
        ->orWhere('order_status_id',23)
        ->orWhere('order_status_id',27)
        ->orWhere('order_status_id',3)
        ->orWhere('order_status_id',20)
        ->where('payment_code', '<>', 'out_ecommerce')->select('customer_id')->get();
        return json_encode($result);
    }

    public function Generate(Request $request)
    {
        Generate::dispatch($request->input())->onQueue("high");
    }


}
