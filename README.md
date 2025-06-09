# GymVerse

## Par Projektu

GymVerse ir fitnesa sekošanas platforma, kas palīdz lietotājiem sasniegt savus fitnesa mērķus, izmantojot pašveidotus treniņu šablonus/veidnes, detalizētu progresa sekošanu un intuitīvu lietotāja pieredzi. Mana platforma ir pieejama gan mobilajās ierīcēs, gan datoros, nodrošinot ērtu pieeju jebkurā vietā un laikā.

## Galvenās Funkcijas

### 🏠 Sākumlapa
- Personalizēts informācijas panelis ar šodienas plānotajiem treniņiem
- Kalendāra skats ar ieplānotajiem treniņiem un atpūtas dienām
- Ātras darbības pogas populārākajiem šabloniem
- Tuvojošos treniņu pārskats
- Ķermeņa mērījumu ātra atjaunināšana

### 💪 Treniņu Sekošana
- Vienkāršs treniņa uzsākšanas process gan no šabloniem, gan ātrs starts
- Reāllaika treniņa sekošana ar svaru, atkārtojumu un grūtības līmeni
- Intuitīvs atpūtas taimeris ar nākamā vingrinājuma priekšskatījumu
- Detalizēts treniņa kopsavilkums ar galvenajiem rādītājiem
- Automātiska sinhronizācija un saglabāšana

### 📋 Šablonu Pārvaldība
- Personalizētu treniņu šablonu izveide un pārvaldība
- Vienkārša vingrinājumu pievienošana un kārtošana
- Sēriju, atkārtojumu un atpūtas laika iestatīšana
- Šablonu atkārtošana noteiktās nedēļas dienās

### 📊 Treniņu Vēsture
- Detalizēts pārskats par visiem veiktajiem treniņiem
- Filtri pēc datuma, treniņa veida un šablona
- Iepriekšējo treniņu salīdzināšana

### 🎯 Mērķu Uzstādīšana
- Dažādu mērķu tipu izveide (spēka, izturības, u.c.)
- Progresa vizuāla attēlošana
- Termiņu iestatīšana un atskaites punkti
- Automātiska progresa sekošana, balstoties uz treniņu datiem
- Pabeigto mērķu atzīmēšana un svinēšana

## Tehnoloģijas

Projekts izstrādāts, izmantojot:
- HTML5
- CSS3
- JavaScript
- PHP
- MySQL datubāzi

## Instalācija un Iestatīšana priekš macbook

1. Klonējiet repozitoriju:
   git clone https://github.com/KristiansPavlovskis/kvalifikacijas_eksamena_darbs.git

2. Instalējiet HomeBrew:
   - /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   - Lai verificētu ka ir instalēts "brew -v"

3. Instalēt PHP un MySQL"
   - brew install php mysql

4. Startēt MySql serveri:
   - brew services start mysql"
   - Ja grib beigt serveri tad "brew services stop mysql"

5. Uzstādīt MySQL serveri:
   - mysql -u root
   - CREATE DATABASE my_project_db;
   - EXIT;

6. Aiziet uz projekta mapi:
   - cd Documents/kvalifikacijas_eksamena_darbs

7. Startēt lokālu PHP serveri:
   - php -S localhost:8000

8. Savienot kodu uz datubāzi:
  <?php
    $host = 'localhost';
    $dbname = 'my_project_db';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
  ?>

9. Lai instalētu phpMyAdmin ar Homebrew:
   - brew install phpmyadmin

10. Konfigurēt phpMyAdmin
   - mkdir -p ~/Sites/phpmyadmin
   - ln -s /opt/homebrew/share/phpmyadmin ~/Sites/phpmyadmin

11. Startēt php serveri prieks phpMyAdmin
   - cd ~/Sites/phpmyadmin
   - php -S localhost:8080

## Debugging priekš instalācijas ja neiet.

1. Verificēt ka ir phpMyAdmin instalēts pareizi
   -  brew list | grep phpmyadmin
   - ja atgriez "phpmyadmin" tad ir installēts, ja nav tad "brew install phpmyadmin"

2. Serve phpMyAdmin no pareizāz diektorijas
   - cd /opt/homebrew/share/phpmyadmin
   - php -S localhost:8080
   - http://localhost:8080/
   - Ja vēl ir "Not Found error" tad pārbaudam vai phpMyAdmin ir pareizi linkots

3. Pareizi savienot phpMyAdmin uz uz Web pieejamu mapi
   - mkdir -p ~/Sites
   - rm -rf ~/Sites/phpmyadmin
   - ln -s /opt/homebrew/share/phpmyadmin ~/Sites/phpmyadmin
   - cd ~/Sites/phpmyadmin
   - php -S localhost:8080
   - http://localhost:8080

4. Vienlaicīgi ieslēgt MySQL un phpMyAdmin
   - brew services start mysql
   - cd ~/Sites/phpmyadmin
   - php -S localhost:8080

# Ja ir šāds error: "Login without a password is forbidden by configuration" fix.

1. Salabot phpMyAdmin ielogošanos problēmu:
   - nano /opt/homebrew/etc/phpmyadmin.config.inc.php
   - Atrodi šo līniju "$cfg['Servers'][$i]['AllowNoPassword'] = false;"
   - nomaini uz "$cfg['Servers'][$i]['AllowNoPassword'] = true;"
   - Saglabā un izej ārā (CTRL + X, tad Y, tad Enter).
   - cd ~/Sites/phpmyadmin
   - php -S localhost:8080
   - http://localhost:8080/

2. Salabot PHP servera neiešanu
   - atvert jaunu termināli un tad aiziet uz savu projekta mapi "cd ~/Documents/kvalifikacijas_eksamena_darbs"
   - php -S localhost:8000
   - http://localhost:8000/


## Lietotāja Saskarnes Plūsmas

### Mobilā Versija

#### Sākumlapa
- Pārskatāms dizains ar treniņiem
- Kalendāra skats ar dienas plānu
- Ātras darbības pogas treniņu uzsākšanai

<img src="https://github.com/user-attachments/assets/a1905ee7-9fcb-41c8-95e4-fc8969c5cb78" width="400" alt="Gymverse lapa"/>

#### Aktīvs Treniņš
- Pilnekrāna režīms ar pašreizējo vingrinājumu
- Vienkārša svara un atkārtojumu ievade
- Grūtības pakāpes novērtējums ar emocijzīmēm
- Pilnekrāna atpūtas taimeris
- Nākamā vingrinājuma priekšskatījums

<img src="https://github.com/user-attachments/assets/6b901f04-153c-4277-b535-2a735cfce5e5" width="400" alt="Gymverse lapa"/>

#### Šablonu Pārvalde
- Vertikāls saraksts ar šabloniem
- Vienkārša jaunu šablonu izveide
- Vingrinājumu secības pārkārtošana ar vilkšanu

<img src="https://github.com/user-attachments/assets/3121824f-b5b0-40cd-b1de-1a0096ad22d5" width="400" alt="Gymverse lapa"/>

#### Vēsture
- Hronoloģisks treniņu saraksts
- Paplašināmi treniņu ieraksti ar detaļām
- Filtrēšanas iespējas

<img src="https://github.com/user-attachments/assets/bb6439e8-21bb-429b-b0ed-d3b79fd81805" width="400" alt="Gymverse lapa"/>

#### Mērķi
- Vizuālie progresa apļi
- Ātras atjaunināšanas iespēja
- Kategoriju filtri

<img src="https://github.com/user-attachments/assets/b44359a1-57de-4216-90bd-5b7af52e155c" width="400" alt="Gymverse lapa"/>

### Datora Versija

#### Sākumlapa
- Vairāku paneļu interfeiss ar kalendāru centrā
- Detalizēti statistikas rādītāji
- Ātrās darbības labajā malā

<img src="https://github.com/user-attachments/assets/9cf71306-a5fa-41a9-968c-2d03abbd3acc" width="800" alt="Gymverse lapa"/>

#### Aktīvs Treniņš
- Trīs paneļu dizains:
  - Pilns treniņa plāns kreisajā pusē
  - Pašreizējais vingrinājums centrā
  - Statistika un taimeris labajā pusē
- Tastatūras saīsnes ērtai pārvaldībai

<img src="https://github.com/user-attachments/assets/fc1b1b7d-2e48-4e38-ac92-deb01c9f5dfb" width="800" alt="Gymverse lapa"/>

#### Šablonu Pārvalde
- Divpaneļu redaktors ar priekšskatījumu
- Detalizētas vingrinājumu opcijas
- Vilkt un nomest funkcionalitāte

<img src="https://github.com/user-attachments/assets/84476a3a-d8b8-444f-b06d-c47f619961bc" width="800" alt="Gymverse lapa"/>

#### Vēsture
- Tabulas skats ar sortējamām kolonnām
- Grafiskās statistikas vizualizācijas
- Eksporta iespējas

<img src="https://github.com/user-attachments/assets/050f93f2-b398-44b9-b553-801dcef51b79" width="800" alt="Gymverse lapa"/>

#### Mērķi
- Detalizēta progresa sekošana ar grafikiem
- Mērķu savstarpējās saistības
- Ieteikumu sistēma

<img src="https://github.com/user-attachments/assets/4d44e6d2-9aa3-4692-a2a5-f4dde2f989f4" width="800" alt="Gymverse lapa"/>

## Administratora Funkcijas

<img src="https://github.com/user-attachments/assets/889a6ae6-7d39-49a0-90c9-507c45672b7d" width="800" alt="Gymverse lapa"/>

### Lietotāju Pārvaldība
- Lietotāju kontu pārskatīšana un rediģēšana
- Statusa mainīšana (aktīvs/apturēts)
- Paroles atiestatīšana
- Aktivitātes monitorings

<img src="https://github.com/user-attachments/assets/2f799a35-9019-44fa-bfd6-8d7e22cc7eb0" width="800" alt="Gymverse lapa"/>

### Globālie Šabloni
- Visiem lietotājiem pieejamo šablonu izveide
- Rediģēšana un publicēšanas kontrole
- Izmantošanas statistikas pārskatīšana

<img src="https://github.com/user-attachments/assets/20a4513e-2dda-4fc1-bd46-9a7830beb0d4" width="800" alt="Gymverse lapa"/>

### Vingrinājumu Bibliotēka
- Jaunu vingrinājumu pievienošana datubāzei
- Detalizētu aprakstu un mediju pievienošana
- Kategorizācija un muskuļu grupu piesaiste
- Saistīto vingrinājumu iestatīšana

<img src="https://github.com/user-attachments/assets/336488e0-2fe7-4c1f-8093-a5219762a81d" width="800" alt="Gymverse lapa"/>

## Nākotnes plāni

- Sociālās funkcijas, piemēram, pievienot draugus un sūtīt cilvēkiem savus izveidotos šablonus, un sarakstīties.
- Uztura sekošanas integrācija, kur var pievienot dienas ēsto, lai sekotu līdzi kalorijām.
- Ūdens daudzumu dzeršanas integrācija, lai sekotu līdzi dienas izdertajam daudzumam.
- Izaicinājumu veidošana, kur var uzaicinā draugus, un tad sacensties kurš ātrāk izdarīs.
- Tādu membership pirkšanas sadaļu, kur ar lielāku pirkumu nāk savējās krutās lomas, un automātiski izveidotas šabloni skatoties no mērķiem un no pirkuma.
- Sasniegumu sistēmu, kura iedos tev jaunu nozīmīti (badge), skatoties kādas lietas esi izdarijis. (Piem, pacēli 100kg, un dabūji tādu zīmīti foršu.)


## Kontakti

- E-pasts: kristianspavlovskis@gmail.com
- GitHub: [https://github.com/KristiansPavlovskis/kvalifikacijas_eksamena_darbs](https://github.com/KristiansPavlovskis/kvalifikacijas_eksamena_darbs)
- Tīmekļa vietne: Vēl nav izveidota domēna
- dokuments: [Pavlovskis_Kristians_kvalifikacijas_eksamena_dokuments.docx](https://github.com/user-attachments/files/20646185/Pavlovskis.Kristians.4PT-2_1.1.3.github.docx)
