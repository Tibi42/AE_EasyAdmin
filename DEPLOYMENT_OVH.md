# Mise en ligne sur un VPS OVH

Guide pour déployer l’application **AE_EasyAdmin** (Symfony 8, PHP 8.4) sur un serveur privé virtuel OVH.

---

## 1. Prérequis côté VPS

- Un **VPS OVH** (VPS Starter, Value, Essential, etc.)
- Accès **SSH** (root ou utilisateur sudo)
- Un **nom de domaine** pointant vers l’IP du VPS (optionnel mais recommandé)

---

## 2. Préparer le serveur

### Connexion SSH

```bash
ssh root@VOTRE_IP_OVH
# ou (utilisateur standard, ex. ubuntu)
ssh ubuntu@VOTRE_IP_OVH
```

**Important :** Les commandes d’installation ci-dessous nécessitent les droits administrateur. Si vous n’êtes pas root, préfixez par **`sudo`** (ex. `sudo apt update`).

### Mise à jour et paquets de base

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git unzip software-properties-common
```

### Installer PHP 8.4

OVH propose souvent PHP via **ondrej/php** :

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-common php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-intl php8.4-pgsql php8.4-pdo php8.4-ctype php8.4-iconv php8.4-gd
```

*(Si vous utilisez MySQL au lieu de PostgreSQL, remplacez `php8.4-pgsql` par `php8.4-mysql`.)*

### Installer Nginx (ou Apache)

**Nginx :**

```bash
sudo apt install -y nginx
```

**Apache (alternative) :**

```bash
sudo apt install -y apache2 libapache2-mod-php8.4
```

### Base de données

**PostgreSQL** (recommandé si vous utilisez déjà PostgreSQL en dev) :

```bash
sudo apt install -y postgresql postgresql-contrib
sudo -u postgres createuser -P -e mon_utilisateur
sudo -u postgres createdb -O mon_utilisateur ae_easyadmin
```

**MySQL/MariaDB** (alternative) :

```bash
sudo apt install -y mariadb-server
sudo mysql_secure_installation
mysql -u root -p -e "CREATE DATABASE ae_easyadmin; CREATE USER 'mon_user'@'localhost' IDENTIFIED BY 'Rapupyxe'; GRANT ALL ON ae_easyadmin.* TO 'mon_user'@'localhost'; FLUSH PRIVILEGES;"
```

---

## 3. Déployer l’application

### Créer un utilisateur dédié (recommandé)

```bash
sudo adduser --disabled-password --gecos "" symfony
```

### Cloner le projet (ou transférer les fichiers)

**Option A – Git :**

```bash
su - symfony
cd /home/symfony
git clone https://github.com/tibi42/AE_EasyAdmin.git
cd AE_EasyAdmin
```

**Option B – Transfert (depuis votre PC) :**

Depuis votre machine locale (PowerShell) :

```powershell
scp -r C:\laragon\www\AE_EasyAdmin symfony@193.70.86.221:/home/symfony/
```

Puis sur le serveur :

```bash
cd /home/symfony/AE_EasyAdmin
```

### Fichier d’environnement `.env` (à créer avant `composer install`)

**Important :** Créez le fichier `.env` (avec au minimum `APP_ENV=prod`) **avant** de lancer `composer install`. Sinon le script post-install (`cache:clear`) s’exécute en environnement dev et échoue (DebugBundle absent en prod).

Copier le modèle puis adapter :

```bash
cd /home/symfony/AE_EasyAdmin
cp .env.dist .env
nano .env
```

Ou créer `.env` à la main avec au minimum `APP_ENV=prod` et `DATABASE_URL=...`.

### Installer les dépendances (production)

```bash
cd /home/symfony/AE_EasyAdmin
# Composer en $HOME (utilisateur symfony sans sudo) :
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=$HOME --filename=composer
php -r "unlink('composer-setup.php');"
# Si le script post-install échoue (DebugBundle), utiliser --no-scripts puis cache:clear à la main :
php ~/composer install --no-dev --optimize-autoloader --no-scripts
php bin/console cache:clear --env=prod
```

*(Si `.env` contient déjà `APP_ENV=prod` avant l’install, vous pouvez omettre `--no-scripts` : `php ~/composer install --no-dev --optimize-autoloader`.)*

Exemple de contenu pour `.env` (à adapter) :

```env
APP_ENV=prod
APP_SECRET=votre_secret_long_et_aleatoire
DATABASE_URL="postgresql://mon_utilisateur:mot_de_passe@127.0.0.1:5432/ae_easyadmin?serverVersion=16&charset=utf8"
# Si MySQL :
# DATABASE_URL="mysql://mon_user:mot_de_passe@127.0.0.1:3306/ae_easyadmin?serverVersion=mariadb-10.6"

# Mailer (ex. Gmail / SMTP OVH)
MAILER_DSN=null://null

# Stripe (si utilisé)
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLIC_KEY=pk_live_...
```

Sauvegarder (Ctrl+O, Entrée, Ctrl+X).

### Droits et répertoires

```bash
chown -R symfony:symfony /home/symfony/AE_EasyAdmin
chmod -R 755 /home/symfony/AE_EasyAdmin
chmod -R 775 /home/symfony/AE_EasyAdmin/var
```

### Cache et migrations

```bash
cd /home/symfony/AE_EasyAdmin
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 4. Configurer le serveur web

### Nginx

Fichier de site (ex. `/etc/nginx/sites-available/ae-easyadmin`) :

```nginx
server {
    listen 80;
    server_name VOTRE_DOMAINE_OU_IP;
    root /home/symfony/AE_EasyAdmin/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

Activer le site :

```bash
ln -s /etc/nginx/sites-available/ae-easyadmin /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### Apache (alternative)

Activer le module et le rewrite :

```bash
a2enmod rewrite
a2enmod proxy_fcgi
a2enconf php8.4-fpm
```

Créer un virtual host (ex. `/etc/apache2/sites-available/ae-easyadmin.conf`) :

```apache
<VirtualHost *:80>
    ServerName VOTRE_DOMAINE_OU_IP
    DocumentRoot /home/symfony/AE_EasyAdmin/public

    <Directory /home/symfony/AE_EasyAdmin/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Puis :

```bash
a2ensite ae-easyadmin
systemctl reload apache2
```

---

## 5. HTTPS avec Let’s Encrypt (recommandé)

```bash
sudo apt install -y certbot python3-certbot-nginx
# Pour Nginx :
certbot --nginx -d VOTRE_DOMAINE
# Pour Apache :
# certbot --apache -d VOTRE_DOMAINE
```

Renouvellement automatique : déjà géré par un cron de `certbot`.

---

## 6. PHP-FPM (si Nginx)

Vérifier que le pool écoute correctement (souvent `/run/php/php8.4-fpm.sock`) :

```bash
php-fpm8.4 -t
systemctl enable php8.4-fpm
systemctl start php8.4-fpm
```

---

## 7. Vérifications finales

- **URL** : `https://VOTRE_DOMAINE` ou `http://VOTRE_IP`
- **Logs Symfony** : `var/log/prod.log`
- **Logs Nginx** : ` /var/log/nginx/error.log`
- **Permissions** : `var/` doit être writable par l’utilisateur du serveur web (souvent `www-data`). Si vous lancez les commandes en tant que `symfony`, vous pouvez faire :

  ```bash
  chown -R www-data:www-data /home/symfony/AE_EasyAdmin/var
  ```

  Ou configurer Nginx/Apache pour exécuter les scripts avec l’utilisateur `symfony` (configuration avancée).

---

## 8. Déploiement continu (optionnel)

Pour des mises à jour régulières sans tout refaire à la main :

- Créer un script `deploy.sh` sur le serveur qui fait : `git pull`, `composer install --no-dev`, `php bin/console cache:clear --env=prod`, `php bin/console doctrine:migrations:migrate --no-interaction`.
- L’exécuter après chaque push (ou via un webhook / CI).

---

## Résumé des commandes essentielles

| Action              | Commande |
|---------------------|----------|
| Mise à jour code    | `git pull` |
| Dépendances         | `composer install --no-dev --optimize-autoloader` |
| Cache prod          | `php bin/console cache:clear --env=prod` |
| Migrations          | `php bin/console doctrine:migrations:migrate --no-interaction` |
| Vérifier Nginx      | `nginx -t && systemctl reload nginx` |
| Logs Symfony        | `tail -f var/log/prod.log` |

En suivant ces étapes, votre application Symfony est prête à tourner sur votre VPS OVH. En cas d’erreur 502 ou 500, vérifier les logs PHP-FPM (`/var/log/php8.4-fpm.log`), Nginx et `var/log/prod.log`.
