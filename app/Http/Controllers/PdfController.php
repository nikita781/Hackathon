<?php

namespace App\Http\Controllers;

use App\Models\Hackathon;
use App\Models\Project;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Mustache\Engine;
use Mustache_Engine;

class PdfController extends Controller
{
    public function certificate(Hackathon $hackathon): Response
    {
        Gate::authorize('downloadCertificate', $hackathon);

        $user = auth()->user();
        $team = $user->teams()->where('hackathon_id', $hackathon->id)->first();
        $place = $team->place;
        $organization = $hackathon->owner;

        $templatePath = $hackathon->getMedia('template')->first();

        if ($templatePath) {
            $template = file_get_contents($templatePath->getPath());

            $m = new Engine();
            $html = $m->render($template, [
                'hackathonTitle' => $hackathon->title,
                'userName' => $user->name,
                'userNickname' => $user->nickname,
                'place' => $place,
                'organizatorNickname' => $organization->nickname,
                'startTime' => $hackathon->event_start->format('d.m.Y'),
                'endTime' => $hackathon->event_end->format('d.m.Y'),
                'seal' => null,
            ]);

            $width = ($templatePath->getCustomProperty('width_mm') ?? 297) * 2.8346;
            $height = ($templatePath->getCustomProperty('height_mm') ?? 210) * 2.8346;
            $customPaper = [0, 0, $width, $height];

            if ($width === 0.0 || $height === 0.0) {
                $customPaper = "A4";
            }

            $pdf = Pdf::loadHTML($html)
                ->setOption(['defaultFont' => 'Helvetica'])
                ->setOption('margin-top', 0)
                ->setOption('margin-bottom', 0)
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setPaper('a4', 'landscape')
                ->setOption('dpi', 300)
                ->setOption('zoom', 1.0)
                ->setPaper($customPaper);

            return $pdf->download("certificate-{$user->nickname}.pdf");
        }

        $customPaper = [0, 0, 1032, 732];

        $pdf = Pdf::loadView('certificate', [
            'hackathonTitle' => $hackathon->title,
            'userName' => $user->name,
            'userNickname' => $user->nickname,
            'place' => $place,
            'organizatorNickname' => $organization->nickname,
            'startTime' => $hackathon->event_start->format('d.m.Y'),
            'endTime' => $hackathon->event_end->format('d.m.Y'),
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
            ->with([
                'users',
                'projects' => fn($q) => $q->where('status', Project::PUBLISHED)
            ])
            ->orderBy('place')
            ->get();

        $pdf = Pdf::loadView('report', [
            'hackathon' => $hackathon,
            'teams' => $teams,
        ])->setPaper('a4');

        return $pdf->download("protocol-{$hackathon->slug}.pdf");
    }
}
