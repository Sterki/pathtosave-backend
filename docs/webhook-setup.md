# Configuración del Webhook de Supabase

## 📋 Descripción

Este documento explica cómo configurar el webhook automático para crear perfiles y categorías cuando se registra un nuevo usuario en Supabase.

## 🔧 Configuración en Supabase

### 1. Crear un Webhook en Supabase

1. Ve a tu proyecto de Supabase Dashboard
2. Navega a **Database** > **Webhooks**
3. Haz clic en **Create a new hook**
4. Configura el webhook con los siguientes datos:

**Configuración del Webhook:**

-   **Name**: `user-creation-webhook`
-   **Table**: `auth.users`
-   **Events**: `INSERT`
-   **Type**: `HTTP Request`
-   **Method**: `POST`
-   **URL**: `https://tu-dominio.com/webhook/supabase`
-   **Headers**:
    ```json
    {
        "Content-Type": "application/json",
        "Authorization": "Bearer tu-token-secreto"
    }
    ```

### 2. Configurar Variables de Entorno

Asegúrate de tener estas variables en tu archivo `.env.local`:

```env
# Supabase
SUPABASE_JWT_SECRET="tu-jwt-secret-de-supabase"
DATABASE_URL="postgresql://postgres:tu-password@db.tu-proyecto.supabase.co:5432/postgres?sslmode=require"

# Opcional: Para autenticación del webhook
WEBHOOK_SECRET="tu-token-secreto-para-webhook"
```

## 🚀 Funcionamiento

### Flujo Automático

1. **Usuario se registra en Supabase** → Supabase crea el usuario en `auth.users`
2. **Supabase dispara el webhook** → Envía POST a `/webhook/supabase`
3. **Symfony procesa el webhook** → Extrae datos del usuario
4. **Se crea automáticamente**:
    - ✅ Perfil del usuario en la tabla `profiles`
    - ✅ Categorías predeterminadas en la tabla `categories`

### Datos del Webhook

El webhook de Supabase envía un payload como este:

```json
{
    "type": "INSERT",
    "table": "auth.users",
    "record": {
        "id": "uuid-del-usuario",
        "email": "usuario@ejemplo.com",
        "created_at": "2024-01-01T00:00:00Z",
        "user_metadata": {
            "display_name": "Usuario"
        }
    },
    "schema": "public",
    "old_record": null
}
```

## 🧪 Pruebas

### Para Desarrollo Local

**Opción 1: Comando Manual (Recomendado para desarrollo)**

```bash
# Crear usuario manualmente después de registrarlo en Supabase
C:\xampp\php\php.exe bin/console app:create-user "uuid-del-usuario" "usuario@ejemplo.com"
```

**Opción 2: Probar el Webhook con ngrok**

1. Instalar ngrok: https://ngrok.com/download
2. Iniciar túnel: `ngrok http 8000`
3. Usar la URL HTTPS en Supabase webhook
4. Probar con curl:

```bash
curl -X POST https://tu-ngrok-url.ngrok.io/webhook/supabase \
  -H "Content-Type: application/json" \
  -d '{
    "type": "INSERT",
    "table": "auth.users",
    "record": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "email": "test@ejemplo.com",
      "created_at": "2024-01-01T00:00:00Z"
    },
    "schema": "public",
    "old_record": null
  }'
```

### Respuesta Exitosa

```json
{
    "status": "success",
    "message": "User profile and categories created successfully",
    "userId": "550e8400-e29b-41d4-a716-446655440000",
    "email": "test@ejemplo.com"
}
```

## 📝 Logs

El webhook registra todas las operaciones en los logs de Symfony:

-   **Información**: Eventos recibidos y procesados
-   **Advertencias**: Datos faltantes o eventos no manejados
-   **Errores**: Fallos en la creación de perfiles/categorías

## 🔍 Monitoreo

Para monitorear el funcionamiento:

1. **Logs de Symfony**: Revisa `var/log/dev.log` o `var/log/prod.log`
2. **Base de datos**: Verifica que se crearon los registros en `profiles` y `categories`
3. **API Platform**: Usa `/api/profiles` y `/api/categories` para verificar los datos

## 🛠️ Comando Manual (Backup)

Si necesitas crear un usuario manualmente, puedes usar el comando:

```bash
php bin/console app:create-user "uuid-del-usuario" "usuario@ejemplo.com"
```

## 🔒 Seguridad

-   El webhook está configurado para aceptar solo eventos `INSERT` en `auth.users`
-   Se valida que los datos requeridos (`id` y `email`) estén presentes
-   Se verifica que no exista ya un perfil para el usuario
-   Los errores se registran sin exponer información sensible

## 📚 Endpoints Disponibles

-   **POST** `/webhook/supabase` - Webhook principal de Supabase
-   **GET** `/api/profiles` - Listar perfiles (requiere autenticación)
-   **GET** `/api/categories` - Listar categorías (requiere autenticación)
