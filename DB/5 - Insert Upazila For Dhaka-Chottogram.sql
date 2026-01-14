START TRANSACTION;

-- =========================================================
-- DHAKA DIVISION — 13 districts
-- Source: district upazila lists per Wikipedia categories/pages. Names use updated spellings. 
-- =========================================================

-- Dhaka
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhamrai',1 FROM districts WHERE name='Dhaka';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dohar',1 FROM districts WHERE name='Dhaka';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Keraniganj',1 FROM districts WHERE name='Dhaka';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nawabganj',1 FROM districts WHERE name='Dhaka';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Savar',1 FROM districts WHERE name='Dhaka';

-- Faridpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Alfadanga',1       FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhanga',1           FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Boalmari',1         FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Charbhadrasan',1    FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Faridpur Sadar',1   FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madhukhali',1       FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nagarkanda',1       FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sadarpur',1         FROM districts WHERE name='Faridpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Saltha',1           FROM districts WHERE name='Faridpur';

-- Gazipur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gazipur Sadar',1 FROM districts WHERE name='Gazipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaliakair',1     FROM districts WHERE name='Gazipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaliganj',1      FROM districts WHERE name='Gazipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kapasia',1       FROM districts WHERE name='Gazipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sreepur',1       FROM districts WHERE name='Gazipur';

-- Gopalganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gopalganj Sadar',1 FROM districts WHERE name='Gopalganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kashiani',1        FROM districts WHERE name='Gopalganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kotalipara',1      FROM districts WHERE name='Gopalganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Muksudpur',1       FROM districts WHERE name='Gopalganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tungipara',1       FROM districts WHERE name='Gopalganj';

-- Kishoreganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Austagram',1     FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bajitpur',1      FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhairab',1       FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hossainpur',1    FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Itna',1          FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Karimganj',1     FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Katiadi',1       FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kishoreganj Sadar',1 FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kuliarchar',1    FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mithamain',1     FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nikli',1         FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pakundia',1      FROM districts WHERE name='Kishoreganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tarail',1        FROM districts WHERE name='Kishoreganj';

-- Madaripur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madaripur Sadar',1 FROM districts WHERE name='Madaripur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalkini',1         FROM districts WHERE name='Madaripur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajoir',1          FROM districts WHERE name='Madaripur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shibchar',1        FROM districts WHERE name='Madaripur';

-- Manikganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Daulatpur',1        FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ghior',1            FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Harirampur',1       FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manikganj Sadar',1  FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Saturia',1          FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shivalaya',1        FROM districts WHERE name='Manikganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Singair',1          FROM districts WHERE name='Manikganj';

-- Munshiganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gazaria',1           FROM districts WHERE name='Munshiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lohajang',1          FROM districts WHERE name='Munshiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Munshiganj Sadar',1  FROM districts WHERE name='Munshiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sirajdikhan',1       FROM districts WHERE name='Munshiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sreenagar',1         FROM districts WHERE name='Munshiganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tongibari',1         FROM districts WHERE name='Munshiganj';

-- Narayanganj
INSERT INTO upazilas (district_id, name, status) SELECT id,'Araihazar',1        FROM districts WHERE name='Narayanganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bandar',1           FROM districts WHERE name='Narayanganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Narayanganj Sadar',1 FROM districts WHERE name='Narayanganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rupganj',1          FROM districts WHERE name='Narayanganj';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sonargaon',1        FROM districts WHERE name='Narayanganj';

-- Narsingdi
INSERT INTO upazilas (district_id, name, status) SELECT id,'Belabo',1             FROM districts WHERE name='Narsingdi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Monohardi',1          FROM districts WHERE name='Narsingdi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Narsingdi Sadar',1    FROM districts WHERE name='Narsingdi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Palash',1             FROM districts WHERE name='Narsingdi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raipura',1            FROM districts WHERE name='Narsingdi';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shibpur',1            FROM districts WHERE name='Narsingdi';

-- Rajbari
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baliakandi',1     FROM districts WHERE name='Rajbari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Goalanda',1       FROM districts WHERE name='Rajbari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalukhali',1      FROM districts WHERE name='Rajbari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pangsha',1        FROM districts WHERE name='Rajbari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajbari Sadar',1  FROM districts WHERE name='Rajbari';

-- Shariatpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhedarganj',1        FROM districts WHERE name='Shariatpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Damudya',1           FROM districts WHERE name='Shariatpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gosairhat',1         FROM districts WHERE name='Shariatpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Naria',1             FROM districts WHERE name='Shariatpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shariatpur Sadar',1  FROM districts WHERE name='Shariatpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Zajira',1            FROM districts WHERE name='Shariatpur';

-- Tangail
INSERT INTO upazilas (district_id, name, status) SELECT id,'Basail',1        FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bhuapur',1       FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Delduar',1       FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dhanbari',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ghatail',1       FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Gopalpur',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kalihati',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Madhupur',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mirzapur',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nagarpur',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sakhipur',1      FROM districts WHERE name='Tangail';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Tangail Sadar',1 FROM districts WHERE name='Tangail';

-- =========================================================
-- CHATTROGRAM DIVISION — 11 districts
-- =========================================================

-- Chattogram
INSERT INTO upazilas (district_id, name, status) SELECT id,'Anwara',1        FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Banshkhali',1    FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Boalkhali',1     FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chandanaish',1   FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fatikchhari',1   FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hathazari',1     FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lohagara',1      FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mirsharai',1     FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Patiya',1        FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rangunia',1      FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raozan',1        FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sandwip',1       FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Satkania',1      FROM districts WHERE name='Chattogram';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sitakunda',1     FROM districts WHERE name='Chattogram';

-- Cox's Bazar
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chakaria',1           FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Cox''s Bazar Sadar',1 FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kutubdia',1           FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Maheshkhali',1        FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Pekua',1              FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ramu',1               FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Teknaf',1             FROM districts WHERE name='Cox''s Bazar';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ukhiya',1             FROM districts WHERE name='Cox''s Bazar';

-- Cumilla
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barura',1                 FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Brahmanpara',1            FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Burichong',1              FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chandina',1               FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chauddagram',1            FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Cumilla Adarsha Sadar',1  FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Cumilla Sadar Dakshin',1  FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Daudkandi',1              FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Debidwar',1               FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Homna',1                  FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Laksam',1                 FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lalmai',1                 FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manoharganj',1            FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Meghna',1                 FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Muradnagar',1             FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nangalkot',1              FROM districts WHERE name='Cumilla';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Titas',1                  FROM districts WHERE name='Cumilla';

-- Feni
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chhagalnaiya',1  FROM districts WHERE name='Feni';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Daganbhuiyan',1  FROM districts WHERE name='Feni';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Feni Sadar',1    FROM districts WHERE name='Feni';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Fulgazi',1       FROM districts WHERE name='Feni';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Parshuram',1     FROM districts WHERE name='Feni';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sonagazi',1      FROM districts WHERE name='Feni';

-- Brahmanbaria
INSERT INTO upazilas (district_id, name, status) SELECT id,'Akhaura',1            FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ashuganj',1           FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bancharampur',1       FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bijoynagar',1         FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Brahmanbaria Sadar',1 FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kasba',1              FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nabinagar',1          FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Nasirnagar',1         FROM districts WHERE name='Brahmanbaria';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sarail',1             FROM districts WHERE name='Brahmanbaria';

-- Noakhali
INSERT INTO upazilas (district_id, name, status) SELECT id,'Begumganj',1       FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chatkhil',1        FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Companiganj',1     FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hatiya',1          FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kabirhat',1        FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Noakhali Sadar',1  FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Senbagh',1         FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Sonaimuri',1       FROM districts WHERE name='Noakhali';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Subarnachar',1     FROM districts WHERE name='Noakhali';

-- Lakshmipur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kamalnagar',1        FROM districts WHERE name='Lakshmipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lakshmipur Sadar',1  FROM districts WHERE name='Lakshmipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Raipur',1            FROM districts WHERE name='Lakshmipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ramganj',1           FROM districts WHERE name='Lakshmipur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ramgati',1           FROM districts WHERE name='Lakshmipur';

-- Chandpur
INSERT INTO upazilas (district_id, name, status) SELECT id,'Chandpur Sadar',1 FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Faridganj',1      FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Haimchar',1       FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Hajiganj',1       FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kachua',1         FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Matlab Dakshin',1 FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Matlab Uttar',1   FROM districts WHERE name='Chandpur';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Shahrasti',1      FROM districts WHERE name='Chandpur';

-- Khagrachhari
INSERT INTO upazilas (district_id, name, status) SELECT id,'Dighinala',1          FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Guimara',1            FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Khagrachhari Sadar',1 FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lakshmichhari',1      FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Mahalchhari',1        FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Manikchhari',1        FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Matiranga',1          FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Panchhari',1          FROM districts WHERE name='Khagrachhari';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ramgarh',1            FROM districts WHERE name='Khagrachhari';

-- Rangamati
INSERT INTO upazilas (district_id, name, status) SELECT id,'Baghaichhari',1           FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Barkal',1                 FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Belaichhari',1            FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Juraichhari',1            FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kaptai',1                 FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Kawkhali (Betbunia)',1    FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Langadu',1                FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Naniarchar',1             FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rajasthali',1             FROM districts WHERE name='Rangamati';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rangamati Sadar',1        FROM districts WHERE name='Rangamati';

-- Bandarban
INSERT INTO upazilas (district_id, name, status) SELECT id,'Alikadam',1           FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Bandarban Sadar',1    FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Lama',1               FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Naikhongchhari',1     FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Rowangchhari',1       FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Ruma',1               FROM districts WHERE name='Bandarban';
INSERT INTO upazilas (district_id, name, status) SELECT id,'Thanchi',1            FROM districts WHERE name='Bandarban';

COMMIT;
