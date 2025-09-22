<?php

namespace App\Http\Controllers;

use App\Models\Hackathon;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PdfController extends Controller
{
    public function certificate(Hackathon $hackathon): Response
    {
        Gate::authorize('downloadCertificate', $hackathon);

        $user = auth()->user();

        $place = $user->teams()->where('hackathon_id', $hackathon->id)->first()->place;

        $organization = $hackathon->owner;

        $customPaper = [0, 0, 1032, 732];

        $pdf = Pdf::loadView('certificate', [
            'hackathon' => $hackathon,
            'user' => $user,
            'place' => $place,
            'organization' => $organization,
            'seal' => null,
        ])
            ->setOption(['defaultFont' => 'Helvetica'])
            ->setPaper($customPaper);

        return $pdf->download("certificate-{$user->nickname}.pdf");
    }

    public function protocol(Hackathon $hackathon): Response
    {
        Gate::authorize('downloadReport', $hackathon);

        $teams = $hackathon->teams()
            ->with(['users'])
            ->orderBy('place')
            ->get();

        $pdf = Pdf::loadView('report', [
            'hackathon' => $hackathon,
            'teams' => $teams,
        ])->setPaper('a4');

        return $pdf->download("protocol-{$hackathon->slug}.pdf");
    }
}
