<x-layouts.app :title="__('Tutoriales')">
    <style>
        .tut-wrap {
            --line: rgba(255,255,255,.13);
            --panel: rgba(14,16,30,.82);
            --text: #f8fbff;
            --muted: rgba(248,251,255,.64);
            --cyan: #27e0ff;
            --green: #31f7a4;
            color: var(--text);
            margin: -24px;
            min-height: calc(100dvh - 1px);
            padding: 24px;
            background:
                radial-gradient(circle at 16% 10%, rgba(255,61,136,.18), transparent 30%),
                radial-gradient(circle at 88% 14%, rgba(39,224,255,.16), transparent 34%),
                linear-gradient(180deg, #080817 0%, #05050d 100%);
        }

        .tut-shell { width: min(1180px, 100%); margin: 0 auto; display: grid; gap: 18px; }
        .tut-head { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; border: 1px solid var(--line); border-radius: 22px; background: var(--panel); padding: 20px; box-shadow: 0 28px 90px rgba(0,0,0,.34); }
        .tut-head h1 { margin: 0; font-size: clamp(28px, 5vw, 48px); line-height: 1; font-weight: 900; }
        .tut-head p { margin: 8px 0 0; color: var(--muted); max-width: 720px; line-height: 1.55; }
        .tut-btn { min-height: 44px; border-radius: 12px; border: 1px solid var(--line); color: var(--text); background: rgba(255,255,255,.08); padding: 0 14px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; font-weight: 900; cursor: pointer; }
        .tut-btn.primary { color: #061014; border-color: transparent; background: linear-gradient(135deg, var(--green), var(--cyan)); }
        .tut-btn.danger { color: #ff9aac; border-color: rgba(255,61,136,.34); background: rgba(255,61,136,.10); }
        .notice { border-radius: 16px; border: 1px solid rgba(49,247,164,.22); background: rgba(49,247,164,.10); padding: 14px 16px; color: #baffdc; font-weight: 800; }
        .errors { border-radius: 16px; border: 1px solid rgba(255,61,136,.24); background: rgba(255,61,136,.10); padding: 14px 16px; color: #ffb5c5; font-weight: 800; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .card { border: 1px solid var(--line); border-radius: 20px; background: var(--panel); padding: 18px; display: grid; gap: 14px; box-shadow: 0 24px 80px rgba(0,0,0,.26); }
        .card h2 { margin: 0; font-size: 20px; font-weight: 900; }
        label { display: grid; gap: 7px; color: rgba(248,251,255,.76); font-size: 13px; font-weight: 900; }
        input, textarea { width: 100%; border: 1px solid var(--line); border-radius: 14px; outline: 0; color: var(--text); background: rgba(255,255,255,.08); padding: 12px; font: inherit; }
        textarea { min-height: 132px; resize: vertical; line-height: 1.45; }
        input[type="file"] { padding: 10px; }
        .preview { border: 1px dashed rgba(255,255,255,.22); border-radius: 16px; padding: 12px; color: var(--muted); }
        .preview img, .preview video { width: 100%; max-height: 260px; border-radius: 14px; object-fit: contain; background: #050711; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }

        @media (max-width: 860px) {
            .grid { grid-template-columns: 1fr; }
            .tut-wrap { margin: -16px; padding: 16px; }
        }
    </style>

    <div class="tut-wrap">
        <div class="tut-shell">
            <header class="tut-head">
                <div>
                    <h1>Tutoriales</h1>
                    <p>Solo el admin sube o cambia tutoriales. La pantalla publica de NetCode solo muestra el texto, imagen o video que guardes aqui.</p>
                </div>
                <a class="tut-btn" href="{{ route('dashboard') }}">Volver dashboard</a>
            </header>

            @if (session('success'))
                <div class="notice">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="errors">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <section class="grid">
                @foreach ($tutorials as $key => $tutorial)
                    <article class="card">
                        <h2>{{ $labels[$key] }}</h2>

                        <form method="POST" action="{{ route('admin.tutoriales.update', $key) }}" enctype="multipart/form-data" class="card" style="padding:0;border:0;background:transparent;box-shadow:none;">
                            @csrf
                            @method('PUT')

                            <label>
                                Titulo
                                <input name="title" value="{{ old("tutorials.$key.title", $tutorial['title']) }}" required maxlength="120">
                            </label>

                            <label>
                                Pasos del tutorial
                                <textarea name="steps" placeholder="Un paso por linea">{{ old("tutorials.$key.steps", implode("\n", $tutorial['steps'] ?? [])) }}</textarea>
                            </label>

                            <label>
                                Imagen o video
                                <input type="file" name="media" accept="image/*,video/mp4,video/quicktime,video/webm">
                            </label>

                            <div class="actions">
                                <button class="tut-btn primary" type="submit">Guardar tutorial</button>
                            </div>
                        </form>

                        <div class="preview">
                            @if (($tutorial['media_type'] ?? null) === 'video' && ($tutorial['media_url'] ?? null))
                                <video src="{{ $tutorial['media_url'] }}" controls playsinline></video>
                            @elseif (($tutorial['media_type'] ?? null) === 'image' && ($tutorial['media_url'] ?? null))
                                <img src="{{ $tutorial['media_url'] }}" alt="{{ $tutorial['title'] }}">
                            @else
                                Sin imagen o video cargado.
                            @endif
                        </div>

                        @if ($tutorial['media_url'] ?? null)
                            <form method="POST" action="{{ route('admin.tutoriales.media.destroy', $key) }}">
                                @csrf
                                @method('DELETE')
                                <button class="tut-btn danger" type="submit">Eliminar archivo</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-layouts.app>
