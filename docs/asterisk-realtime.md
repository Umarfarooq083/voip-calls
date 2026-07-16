# Asterisk PJSIP Realtime Configuration

## 1. MySQL Database Setup

Create a dedicated database for Asterisk:

```sql
CREATE DATABASE asterisk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'asterisk'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON asterisk.* TO 'asterisk'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Asterisk Configuration Files

### /etc/asterisk/res_odbc.conf

```
[asterisk]
enabled => yes
dsn => asterisk
username => asterisk
password => your_secure_password
```

### /etc/asterisk/extconfig.conf

```
[settings]
ps_endpoints => odbc,asterisk,ps_endpoints
ps_auths => odbc,asterisk,ps_auths
ps_aors => odbc,asterisk,ps_aors
ps_contacts => odbc,asterisk,ps_contacts
```

### /etc/asterisk/pjsip.conf

```
[transport-udp]
type=transport
protocol=udp
bind=0.0.0.0

[transport-tcp]
type=transport
protocol=tcp
bind=0.0.0.0

[transport-tls]
type=transport
protocol=tls
bind=0.0.0.0

[authtimeout]
type=auth
auth_type=userpass

[global]
type=global
userimize=yes
```

## 3. Required Asterisk Modules

Ensure these modules are loaded:

```
module load res_odbc.so
module load res_config_odbc.so
module load pjsip.so
module load pjsip_endpoint.so
module load pjsip_auth.so
module load pjsip_aor.so
module load pjsip_contact.so
module load pjsip_exten_state.so
```

Add to /etc/asterisk/modules.conf:

```
[modules]
autoload=yes
preload => res_odbc.so
preload => res_config_odbc.so
```

## 4. MySQL Tables Required

### ps_endpoints
Stores SIP endpoint configurations (extensions/users).
- `id`: Endpoint ID (extension number/name)
- `transport`: Transport protocol (udp, tcp, tls)
- `context`: Dialplan context
- `disallow`: Codecs to disallow
- `allow`: Codecs to allow
- `auth`: Link to authentication
- `aors`: Link to AORs
- `callerid`: Caller ID settings
- etc.

### ps_auths
Stores authentication credentials.
- `id`: Auth ID (usually matches endpoint)
- `auth_type`: Authentication type (userpass)
- `auth_username`: Username for auth
- `auth_password`: Password for auth

### ps_aors
Stores Address of Record (location information).
- `id`: AOR ID (usually matches endpoint)
- `aor_max_contacts`: Max simultaneous contacts
- `aor_qualify_frequency`: Qualify interval

### ps_contacts
Stores contact information for registered devices.
- `id`: Contact ID
- `contact_uri`: SIP URI of the contact
- `contact_status`: Registration status (ok, unavailable)

## 5. Verifying Extension Registration

### Check registration status:
```
asterisk -rx "pjsip show endpoints"
asterisk -rx "pjsip show endpoint <extension>"
asterisk -rx "pjsip show contacts"
```

### Check Asterisk logs:
```
tail -f /var/log/asterisk/full
```

### From Laravel (Web):
Navigate to `/extensions/{id}` to view extension details and registration status.

## 6. Reloading PJSIP After Changes

### Via CLI:
```
asterisk -rx "pjsip reload"
```

### Via PHP (using Symfony Process):
```php
use Symfony\Component\Process\Process;

$process = new Process(['asterisk', '-rx', 'pjsip reload']);
$process->run();

if (!$process->isSuccessful()) {
    Log::error('Failed to reload PJSIP: ' . $process->getErrorOutput());
}
```

## 7. Laravel Database Configuration

Add to your `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asterisk
DB_USERNAME=asterisk
DB_PASSWORD=your_secure_password
```

## 8. Running Migrations

```bash
php artisan migrate
```

This will create:
- `extensions` table (your application data)
- `ps_endpoints`, `ps_auths`, `ps_aors`, `ps_contacts` (Asterisk Realtime tables)

## 9. Routes (Web - Inertia.js)

The following routes are available:

| Method | URI | Action | Middleware |
|--------|-----|--------|------------|
| GET | /extensions | List extensions | auth |
| GET | /extensions/create | Show create form | auth |
| POST | /extensions | Create extension | auth |
| GET | /extensions/{id} | Show extension | auth |
| GET | /extensions/{id}/edit | Show edit form | auth |
| PUT/PATCH | /extensions/{id} | Update extension | auth |
| DELETE | /extensions/{id} | Delete extension | auth |

## 10. Testing Extension Registration

1. Create extension via web form:
   - Login to your Laravel application
   - Navigate to Extensions page
   - Click "Create Extension"
   - Fill in the form (name, extension number, secret/password, etc.)
   - Submit the form

2. Configure SIP phone with:
   - SIP Server: Your Asterisk server IP
   - Username: Extension number (e.g., 1001)
   - Password: Secret set in Laravel
   - Domain: Your Asterisk server IP

3. Check registration in Asterisk:
```bash
asterisk -rx "pjsip show contacts"
```

4. Verify in Laravel:
   - Navigate to Extensions/{id} page
   - Check if "Registered" status shows as true

## 11. Vue.js/Inertia.js Integration

Create Vue components in `resources/js/Pages/Extensions/`:

- `Index.vue` - List all extensions with search/filter
- `Create.vue` - Form to create new extension
- `Edit.vue` - Form to edit existing extension
- `Show.vue` - View extension details

Example Index.vue structure:
```vue
<template>
  <div>
    <Head title="Extensions" />
    <Heading>Extensions</Heading>
    <Button as="Link" href="/extensions/create">
      Create Extension
    </Button>
    <Table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Extension</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="extension in extensions" :key="extension.id">
          <td>{{ extension.name }}</td>
          <td>{{ extension.extension }}</td>
          <td>{{ extension.is_active ? 'Active' : 'Inactive' }}</td>
          <td>
            <Link :href="route('extensions.edit', extension.id)">Edit</Link>
          </tr>
        </tr>
      </tbody>
    </Table>
  </div>
</template>
```