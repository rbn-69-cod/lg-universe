<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\TutorialContent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorialController extends Controller
{
    public function index()
    {
        return view('admin.tutoriales.index', [
            'tutorials' => TutorialContent::all(),
            'labels' => TutorialContent::KEYS,
        ]);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(array_key_exists($key, TutorialContent::KEYS), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'steps' => ['nullable', 'string', 'max:3000'],
            'media' => [
                'nullable',
                'file',
                'max:51200',
                Rule::mimes(['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'mov', 'webm']),
            ],
        ], [
            'media.max' => 'El archivo no puede pesar mas de 50 MB.',
            'media.mimes' => 'Sube imagen JPG/PNG/WEBP/GIF o video MP4/MOV/WEBM.',
        ]);

        TutorialContent::save(
            $key,
            $data['title'],
            preg_split('/\R+/', (string) ($data['steps'] ?? '')) ?: [],
            $request->file('media')
        );

        return back()->with('success', 'Tutorial actualizado correctamente.');
    }

    public function removeMedia(string $key)
    {
        abort_unless(array_key_exists($key, TutorialContent::KEYS), 404);

        TutorialContent::removeMedia($key);

        return back()->with('success', 'Archivo del tutorial eliminado.');
    }
}
