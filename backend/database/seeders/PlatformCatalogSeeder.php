<?php

namespace Database\Seeders;

use App\Models\Plataforma;
use Illuminate\Database\Seeder;

class PlatformCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['NETFLIX', 15, 'Plataforma de streaming de peliculas y series', ['Contenido original exclusivo', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega correo, contrasena, perfil y PIN. Para codigos de hogar o acceso usa NetCode desde tu panel de cliente.', 'El servicio dura 1 mes. No cambies correo, contrasena, perfil ni PIN. El acceso puede requerir codigo de verificacion.'],
            ['DISNEY', 10, 'Plataforma de streaming de peliculas y series', ['Contenido de Disney, Marvel, Pixar y Star Wars', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega correo, contrasena, perfil y PIN asignado. Usa solo el perfil indicado.', 'No modificar datos de la cuenta. Renovacion mensual segun disponibilidad.'],
            ['YOUTUBE PREMIUM', 10, 'YouTube sin anuncios y con funciones premium', ['Sin anuncios', 'Reproduccion en segundo plano', 'Descarga de videos', 'YouTube Music', 'Cuenta vinculada a tu correo'], 'Se activa mediante invitacion o vinculacion al correo indicado por el cliente.', 'No remover la membresia ni cambiar configuraciones familiares.'],
            ['PARAMOUNT+', 8, 'Streaming con Paramount, Nickelodeon y CBS', ['Series y peliculas exclusivas', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega acceso con perfil asignado si corresponde.', 'Uso personal. No compartir ni modificar credenciales.'],
            ['HBO MAX', 7, 'Streaming con HBO, Warner Bros y DC', ['Series y peliculas exclusivas', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega correo, contrasena y perfil asignado.', 'No cambiar perfil, PIN ni datos de cuenta.'],
            ['PRIME VIDEO', 7, 'Streaming de Amazon Prime Video', ['Contenido original de Amazon', 'Series y peliculas exclusivas', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega acceso y perfil asignado si aplica.', 'No usar compras, canales adicionales ni cambiar datos.'],
            ['CRUNCHYROLL MEGAFAN', 5, 'Streaming de anime', ['Amplio catalogo de anime', 'Estrenos simultaneos con Japon', 'Disponible en TV, celular y laptop', 'Descarga para ver sin internet', '1 perfil', '1 dispositivo'], 'Se entrega acceso listo para iniciar sesion.', 'Uso personal. No cambiar credenciales.'],
            ['VIX+', 5, 'Streaming de contenido en espanol', ['Series, peliculas y novelas', 'Contenido original latino', 'Deportes y programas en vivo', 'Disponible en multiples dispositivos'], 'Se entrega usuario y clave o perfil asignado.', 'Uso personal mensual. No modificar datos de la cuenta.'],
            ['IPTV', 12, 'Television por internet', ['Canales en vivo', 'Peliculas y series bajo demanda', 'Compatible con Smart TV, celular y TV Box', 'Requiere internet estable'], 'Se entrega usuario, clave, URL/lista o datos de app segun el dispositivo.', 'La calidad depende de la conexion del cliente. No compartir acceso.'],
            ['SPOTIFY PREMIUM', 7, 'Musica premium sin anuncios', ['Musica sin anuncios', 'Descarga offline', 'Saltos ilimitados', 'Alta calidad de audio'], 'Se activa por invitacion o cuenta configurada segun disponibilidad.', 'No cambiar region, correo ni configuraciones de plan.'],
            ['CHAT GPT PLUS', 15, 'Inteligencia artificial para estudio y trabajo', ['Responde preguntas', 'Genera textos y resumenes', 'Ayuda en estudios y trabajo', 'Disponible en web y app movil', '1 perfil', '1 dispositivo'], 'Se entrega acceso asignado y recomendaciones de uso.', 'No cambiar credenciales, correo ni seguridad de la cuenta.'],
            ['APLE MUSIC', 7, 'Musica premium', ['Mas de 100 millones de canciones', 'Sin anuncios', 'Audio de alta calidad', 'Descarga offline', 'Compatible con iPhone, Android, PC y Mac'], 'Se activa mediante invitacion o datos asignados.', 'No modificar configuraciones familiares ni datos de cuenta.'],
            ['APLE TV+', 7, 'Series y peliculas originales de Apple', ['Contenido original Apple', 'Calidad 4K HDR', 'Descarga offline', 'Compatible con dispositivos Apple, Smart TV y mas'], 'Se entrega acceso o invitacion segun disponibilidad.', 'No modificar datos de cuenta ni compartir acceso.'],
            ['CANVA EDU 1 MES', 4, 'Diseno educativo por 1 mes', ['Plantillas para tareas y presentaciones', 'Trabajo colaborativo', 'Herramientas con IA', 'Almacenamiento en la nube'], 'Se activa en el correo indicado por el cliente o se entrega acceso asignado.', 'Duracion 1 mes. No cambiar correo ni configuraciones del equipo.'],
            ['CANVA EDU 3 MESES', 10, 'Diseno educativo por 3 meses', ['Plantillas para tareas y presentaciones', 'Trabajo colaborativo', 'Herramientas con IA', 'Almacenamiento en la nube'], 'Se activa en el correo indicado por el cliente o se entrega acceso asignado.', 'Duracion 3 meses. No cambiar correo ni configuraciones del equipo.'],
            ['CANVA EDU 6 MESES', 18, 'Diseno educativo por 6 meses', ['Plantillas para tareas y presentaciones', 'Trabajo colaborativo', 'Herramientas con IA', 'Almacenamiento en la nube'], 'Se activa en el correo indicado por el cliente o se entrega acceso asignado.', 'Duracion 6 meses. No cambiar correo ni configuraciones del equipo.'],
        ];

        foreach ($items as $index => [$name, $price, $description, $features, $activation, $terms]) {
            $platform = Plataforma::query()->updateOrCreate(
                ['nombre' => $name],
                [
                    'imagen' => null,
                    'precio' => $price,
                    'descripcion' => $description,
                    'features' => $features,
                    'activacion' => $activation,
                    'terminos' => $terms,
                    'activo' => true,
                    'orden' => $index + 1,
                ]
            );

            $platform->duraciones()->updateOrCreate(
                ['duracion_meses' => 1],
                [
                    'precio' => $price,
                    'activo' => true,
                ]
            );
        }
    }
}
