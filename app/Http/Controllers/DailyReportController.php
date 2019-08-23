<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Model\StudentClass\StudentClass;

use App\Model\Siswa\Siswa;

use App\Model\AssessmentLog\AssessmentLog;

class DailyReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application index.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        return view('daily-report.index', ['active'=>'daily-report']);
    }

    /**
     *
     */
    public function printPdf(Request $request)
    {
        $start_date = $request->session()->get('start_date');
        $end_date = $request->session()->get('end_date');
        $class_id = $request->session()->get('class_id');
        $data = $request->session()->get('data_assessment_log');

        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('daily-report.all_student',[
            'start_date' => $start_date,
            'end_date' => $end_date,
            'class_id' => $class_id,
            'data' => $data 
        ]);

        return $pdf->stream();
    }


    /**
     *
     */
    public function show(Request $request)
    {
        if ($request->ajax()) {
            
            $data = AssessmentLog::whereBetween('date', [$request->get('start_date'), $request->get('end_date')])
            ->leftJoin('tbl_siswa', 'tbl_siswa.id', '=', 'tbl_assessment_log.siswa_id')
            ->where('memorization_type',$request->get('memorization_type'))
            ->where('class_id',$request->get('student_class'))
            ->orderBy('siswa_id', 'asc')->get();

            // Store data in session
            $request->session()->put('start_date', $request->get('start_date'));
            $request->session()->put('end_date', $request->get('end_date'));
            $request->session()->put('class_id', StudentClass::findOrFail($request->get('student_class'))->class_name);
            $request->session()->put('data_assessment_log', $data);


            if($data->isEmpty())
            {
                $empty_data = 
                
                '<div class="alert alert-danger" role="alert">
                    <p> <strong> Maaf, Data yang anda cari tidak ditemukan pada tanggal tersebut !! </strong> </p>
                </div>';

                return $this->getResponse(false,200,$empty_data,'Berhasil menarik data');
            }
            
            $table  = '<table class="table table-striped">';
            $table .= '<thead>';
            $table .= '<tr>';
            $table .= '<th> Siswa </th>';
            $table .= '<th> Surat / Jilid </th>';
            $table .= '<th> Ayat / Halaman </th>';
            $table .= '<th> Tanggal </th>';
            $table .= '</tr>';
            $table .= '</thead>';
            $table .= '<tbody>';

            $old_assessment = null;

            foreach ($data as $assessment) 
            {
                $table .= '<tr>';

                if($assessment->siswa_id != $old_assessment)
                {
                    $table .= '<td>'.$assessment->getSiswa->siswa_name.'</td>';
                    $old_assessment = $assessment->siswa_id;
                }
                else
                {
                    $table .= '<td></td>';
                }
                
                $table .= '<td>'.$assessment->assessment.'</td>';
                $table .= '<td>'.$assessment->range.'</td>';
                $table .= '<td>'. date('d M Y', strtotime($assessment->date)) .'</td>';
                $table .= '</tr>';
            }
            
            $table .= '</tbody>';
            $table .= '</table>';

            return $this->getResponse(true,200,$table,'Berhasil menarik data');
        }
    }
}
