\# Anuncios Lima



Plataforma web desarrollada para la publicación, búsqueda y gestión de anuncios de empleo.



El proyecto permite a los usuarios registrarse, publicar anuncios y consultar oportunidades laborales, además de incorporar herramientas administrativas para gestionar usuarios, categorías, ubicaciones, publicidad y publicaciones.



\## Objetivo del proyecto



Desarrollar una plataforma web que centralice la publicación y búsqueda de oportunidades laborales, facilitando la interacción entre usuarios que publican anuncios y personas interesadas en encontrar empleo.



\## Funcionalidades principales



\### Usuarios



\- Registro de usuarios.

\- Inicio y cierre de sesión.

\- Gestión de cuenta.

\- Recuperación y restablecimiento de contraseña.

\- Verificación mediante correo electrónico.

\- Gestión administrativa de usuarios.



\### Empleos y anuncios



\- Publicación de ofertas de empleo.

\- Visualización del detalle de cada anuncio.

\- Gestión de anuncios publicados.

\- Categorías de empleo.

\- Gestión de lugares.

\- Anuncios destacados.

\- Flujo de publicación mediante diferentes etapas.



\### Administración



\- Gestión de usuarios.

\- Gestión de categorías.

\- Gestión de ubicaciones.

\- Administración de publicidad.

\- Control de anuncios destacados.



\### Notificaciones



El sistema incorpora diferentes notificaciones por correo electrónico, entre ellas:



\- Bienvenida después del registro.

\- Verificación de cuenta.

\- Recuperación de contraseña.

\- Confirmación de cambio de contraseña.

\- Confirmación de publicación.

\- Notificaciones relacionadas con anuncios destacados.

\- Notificaciones administrativas.



\## Tecnologías utilizadas



\- PHP

\- MySQL

\- HTML5

\- CSS3

\- JavaScript

\- Composer

\- Arquitectura MVC



\## Estructura del proyecto



```text

app/

├── controllers/    # Controladores de la aplicación

├── models/         # Modelos y acceso a datos

├── views/          # Interfaces y vistas

└── helpers/        # Funciones auxiliares y notificaciones



public/

└── assets/

&#x20;   ├── css/

&#x20;   ├── images/

&#x20;   └── js/



config/             # Configuración local (no incluida públicamente)

storage/            # Archivos generados y logs

uploads/            # Archivos cargados por usuarios

