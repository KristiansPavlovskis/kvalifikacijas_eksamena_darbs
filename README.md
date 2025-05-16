# GymVerse

## Par Projektu

GymVerse ir fitnesa sekošanas platforma, kas palīdz lietotājiem sasniegt savus fitnesa mērķus, izmantojot pašveidotus treniņu šablonus/veidnes, detalizētu progresa sekošanu un intuitīvu lietotāja pieredzi. Mana platforma ir pieejama gan mobilajās ierīcēs, gan datoros, nodrošinot ērtu pieeju jebkurā vietā un laikā.

![Gymverse Logo]()

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


## Datubāzes Struktūra

### users
- id
- username
- password
- email
- first_name
- last_name
- date_of_birth
- gender
- weight
- initial_weight
- goal_weight
- height
- profile-image
- fitness-level
- created_at
- last_login

### workouts
- id
- user_id
- name
- workout_type
- duration_minutes
- calories_burned
- notes
- rating
- template_id
- created_at
- total_volume

### workout_templates
- id
- name
- description
- difficulty
- estimated_time
- category
- user_id
- created_at
- updated_at

### exercises
- id
- name
- description
- muscle_group
- equipment
- difficulty
- created_by
- is_public
- created_at

### goals
- id
- user_id
- title
- description
- goal_type
- target_value
- current_value
- start_date
- deadline
- created_at
- completed
- completed_at

## Lietotāja Saskarnes Plūsmas

### Mobilā Versija

#### Sākumlapa
- Pārskatāms dizains ar treniņiem
- Kalendāra skats ar dienas plānu
- Ātras darbības pogas treniņu uzsākšanai

![Gymverse lapa]()

#### Aktīvs Treniņš
- Pilnekrāna režīms ar pašreizējo vingrinājumu
- Vienkārša svara un atkārtojumu ievade
- Grūtības pakāpes novērtējums ar emocijzīmēm
- Pilnekrāna atpūtas taimeris
- Nākamā vingrinājuma priekšskatījums

![Gymverse lapa]()

#### Šablonu Pārvalde
- Vertikāls saraksts ar šabloniem
- Vienkārša jaunu šablonu izveide
- Vingrinājumu secības pārkārtošana ar vilkšanu

![Gymverse lapa]()

#### Vēsture
- Hronoloģisks treniņu saraksts
- Paplašināmi treniņu ieraksti ar detaļām
- Filtrēšanas iespējas

![Gymverse lapa]()

#### Mērķi
- Vizuālie progresa apļi
- Ātras atjaunināšanas iespēja
- Kategoriju filtri

![Gymverse lapa]()

### Datora Versija

#### Sākumlapa
- Vairāku paneļu interfeiss ar kalendāru centrā
- Detalizēti statistikas rādītāji
- Ātrās darbības labajā malā

![Gymverse lapa]()

#### Aktīvs Treniņš
- Trīs paneļu dizains:
  - Pilns treniņa plāns kreisajā pusē
  - Pašreizējais vingrinājums centrā
  - Statistika un taimeris labajā pusē
- Tastatūras saīsnes ērtai pārvaldībai

![Gymverse lapa]()

#### Šablonu Pārvalde
- Divpaneļu redaktors ar priekšskatījumu
- Detalizētas vingrinājumu opcijas
- Vilkt un nomest funkcionalitāte

![Gymverse lapa]()

#### Vēsture
- Tabulas skats ar sortējamām kolonnām
- Grafiskās statistikas vizualizācijas
- Eksporta iespējas

![Gymverse lapa]()

#### Mērķi
- Detalizēta progresa sekošana ar grafikiem
- Mērķu savstarpējās saistības
- Ieteikumu sistēma

## Administratora Funkcijas

![Gymverse lapa]()

### Lietotāju Pārvaldība
- Lietotāju kontu pārskatīšana un rediģēšana
- Statusa mainīšana (aktīvs/apturēts)
- Paroles atiestatīšana
- Aktivitātes monitorings

![Gymverse lapa]()

### Globālie Šabloni
- Visiem lietotājiem pieejamo šablonu izveide
- Rediģēšana un publicēšanas kontrole
- Izmantošanas statistikas pārskatīšana

![Gymverse lapa]()

### Vingrinājumu Bibliotēka
- Jaunu vingrinājumu pievienošana datubāzei
- Detalizētu aprakstu un mediju pievienošana
- Kategorizācija un muskuļu grupu piesaiste
- Saistīto vingrinājumu iestatīšana

![Gymverse lapa]()

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