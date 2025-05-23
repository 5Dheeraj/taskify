<?php

namespace App\Http\Controllers;

use App\Models\TrainingUserCertificate;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TrainingCertificateController extends Controller
{
    public function index()
    {
        $certificates = TrainingUserCertificate::where('user_id', auth()->id())->get();
        return view('training.certificates.index', compact('certificates'));
    }

    public function download($id)
    {
        $certificate = TrainingUserCertificate::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $pdf = Pdf::loadView('training.certificates.template', [
            'certificate' => $certificate
        ]);

        return $pdf->download('certificate-' . $certificate->id . '.pdf');
    }
}
