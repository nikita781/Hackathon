<?php

namespace App\Http\Controllers;

use App\Http\Requests\TabRequest;
use App\Models\Hackathon;
use App\Models\Tab;
use Illuminate\Http\Request;

class TabController extends Controller
{
    public function update(TabRequest $request, Hackathon $hackathon)
    {
        $data = $request->validated();
        $tab = $hackathon->tabs()->where('title', $data['title'])->update(
            ['content' => $data['content']]
        );

        return back()->with('success', 'Сохранено');
    }
}
